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
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
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

            return response()->json(['message' => 'Thank you! Your grades have been confirmed.']);
        }

        // ---- type === 'recheck' ----
        $deadline = Setting::get('grade_correction_deadline');
        if ($deadline && now()->gt(\Carbon\Carbon::parse($deadline)->endOfDay())) {
            return response()->json([
                'message' => 'The deadline for grade correction requests has passed (' . \Carbon\Carbon::parse($deadline)->format('M d, Y') . '). Please get in touch with Sir Francisco directly.',
            ], 422);
        }

        $editedItems = json_decode($request->edited_items, true);

        if (! is_array($editedItems) || count($editedItems) === 0) {
            return response()->json(['message' => 'Please indicate which grades are incorrect.'], 422);
        }

        foreach ($editedItems as $item) {
            if (! isset($item['category'], $item['title'], $item['claimed_score'])) {
                return response()->json(['message' => 'One of the edited items is missing required details.'], 422);
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
            return response()->json(['message' => 'Please attach proof/evidence for the recheck request.'], 422);
        }

        if ($existing) {
            $existing->update([
                'notes' => $request->notes,
                'edited_items' => $editedItems,
                'attachment_path' => $attachmentPath,
            ]);

            return response()->json([
                'message' => 'Your recheck request has been updated. Only one active request is allowed while pending, so this is the latest version Sir Francisco will review.',
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
            'message' => 'Your recheck request has been submitted and will be reviewed by Sir Francisco.',
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
                'archived' => (bool) $c->archived,
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
            ? 'Deadline set to ' . \Carbon\Carbon::parse($request->deadline)->format('M d, Y') . '.'
            : 'Deadline removed (no limit for now).');
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

    public function archiveMany(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:grade_corrections,id']);

        GradeCorrection::whereIn('id', $request->ids)->update(['archived' => true]);

        return response()->json(['message' => count($request->ids) . ' request(s) archived.']);
    }

    public function unarchiveMany(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:grade_corrections,id']);

        GradeCorrection::whereIn('id', $request->ids)->update(['archived' => false]);

        return response()->json(['message' => count($request->ids) . ' request(s) restored from archive.']);
    }

    public function cancel(Request $request, GradeCorrection $gradeCorrection)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password || $gradeCorrection->student_id !== $student->id) {
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        if ($gradeCorrection->status !== 'pending') {
            return response()->json(['message' => 'This request can no longer be canceled.'], 422);
        }

        if ($gradeCorrection->attachment_path) {
            Storage::disk('supabase')->delete($gradeCorrection->attachment_path);
        }

        $gradeCorrection->delete();

        return response()->json(['message' => 'Your recheck request has been canceled.']);
    }
}