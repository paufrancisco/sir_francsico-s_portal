<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Setting;
use App\Models\GradeCorrection;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GradeCorrectionController extends Controller
{
    // ---- Student side ----
    public function store(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
            'type' => 'required|in:confirmed,recheck',
            'period' => 'nullable|in:prelim,midterm,prefinal,finals',
            'notes' => 'nullable|string|max:1000',
            'edited_items' => 'required_if:type,recheck|nullable|string',
            'attachment' => 'nullable|image|max:5120',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        if ($request->type === 'confirmed') {
            GradeCorrection::create([
                'student_id' => $student->id,
                'section_id' => $student->section_id,
                'type' => 'confirmed',
                'period' => $request->period,
                'status' => 'resolved',
                'decision' => 'approved',
                'resolved_at' => now(),
            ]);

            return response()->json(['message' => 'Salamat! Na-confirm na ang grades mo.']);
        }

        // ---- type === 'recheck' ----
        $deadline = Setting::get('grade_correction_deadline');
        if ($deadline && now()->gt(\Carbon\Carbon::parse($deadline)->endOfDay())) {
            return response()->json([
                'message' => 'Tapos na ang deadline para sa grade correction requests (' . \Carbon\Carbon::parse($deadline)->format('M d, Y') . '). Makipag-ugnayan na lang kay Sir Francisco.',
            ], 422);
        }

        $editedItems = json_decode($request->edited_items, true);

        if (! is_array($editedItems) || count($editedItems) === 0) {
            return response()->json(['message' => 'Ilagay muna kung alin ang mali sa grades mo.'], 422);
        }

        foreach ($editedItems as $item) {
            if (! isset($item['category'], $item['title'], $item['claimed_score'])) {
                return response()->json(['message' => 'May kulang na detalye sa binagong item.'], 422);
            }
        }

        $existing = GradeCorrection::where('student_id', $student->id)
            ->where('section_id', $student->section_id)
            ->where('period', $request->period)
            ->where('type', 'recheck')
            ->where('status', 'pending')
            ->first();

        $attachmentPath = $existing?->attachment_path;

        if ($request->hasFile('attachment')) {
            if ($existing?->attachment_path) {
                Storage::disk('supabase')->delete($existing->attachment_path);
            }
            $attachmentPath = $request->file('attachment')->store('grade-correction-attachments', 'supabase');
        } elseif (! $existing) {
            return response()->json(['message' => 'Maglagay ng patunay (attachment) para sa recheck request.'], 422);
        }

        if ($existing) {
            $existing->update([
                'notes' => $request->notes,
                'edited_items' => $editedItems,
                'attachment_path' => $attachmentPath,
            ]);

            return response()->json([
                'message' => 'Na-update ang recheck request mo. Isa lang ang active request habang naka-pending, kaya ito ang bagong laman na titingnan ni Sir Francisco.',
                'updated_existing' => true,
            ]);
        }

        GradeCorrection::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'type' => 'recheck',
            'period' => $request->period,
            'notes' => $request->notes,
            'edited_items' => $editedItems,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
            'decision' => null,
            'resolved_at' => null,
        ]);

        return response()->json([
            'message' => 'Naipasa na ang recheck request mo, titingnan ito ni Sir Francisco.',
            'updated_existing' => false,
        ]);
    }

    // ---- Admin side ----
    public function index()
    {
        $corrections = GradeCorrection::with(['student', 'section'])
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'student_id' => $c->student_id,
                'student_name' => $c->student->full_name,
                'student_number' => $c->student->student_number,
                'section' => $c->section?->name,
                'type' => $c->type,
                'notes' => $c->notes,
                'status' => $c->status,
                'decision' => $c->decision,
                'edited_items' => $c->edited_items,
                'attachment_url' => $c->attachment_path
                    ? Storage::disk('supabase')->temporaryUrl($c->attachment_path, now()->addMinutes(30))
                    : null,
                'created_at' => $c->created_at,
                'resolved_at' => $c->resolved_at,
            ]);

        return Inertia::render('Admin/GradeCorrections/Index', [
            'corrections' => $corrections,
            'deadline' => Setting::get('grade_correction_deadline'),
        ]);
    }

    public function setDeadline(Request $request)
    {
        $request->validate(['deadline' => 'nullable|date']);

        Setting::set('grade_correction_deadline', $request->deadline);

        return back()->with('success', $request->deadline
            ? 'Na-set ang deadline sa ' . \Carbon\Carbon::parse($request->deadline)->format('M d, Y') . '.'
            : 'Naalis ang deadline (walang limitasyon ngayon).');
    }

    public function resolve(Request $request, GradeCorrection $gradeCorrection)
    {
        $request->validate([
            'decision' => 'required|in:approved,rejected',
        ]);

        $gradeCorrection->update([
            'status' => 'resolved',
            'decision' => $request->decision,
            'resolved_at' => now(),
        ]);

        return response()->json([
            'correction' => [
                'id' => $gradeCorrection->id,
                'status' => $gradeCorrection->status,
                'decision' => $gradeCorrection->decision,
                'resolved_at' => $gradeCorrection->resolved_at,
            ],
        ]);
    }

    public function cancel(Request $request, GradeCorrection $gradeCorrection)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password || $gradeCorrection->student_id !== $student->id) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        if ($gradeCorrection->status !== 'pending') {
            return response()->json(['message' => 'Hindi na pwedeng kanselahin ang request na ito.'], 422);
        }

        if ($gradeCorrection->attachment_path) {
            Storage::disk('supabase')->delete($gradeCorrection->attachment_path);
        }

        $gradeCorrection->delete();

        return response()->json(['message' => 'Nakansela na ang recheck request mo.']);
    }
}