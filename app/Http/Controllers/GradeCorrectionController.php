<?php

namespace App\Http\Controllers;

use App\Models\Grade;
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
            // 5MB max, gaya ng hiningi — 5120 KB (Laravel image validation is in KB)
            'attachment' => 'required_if:type,recheck|nullable|image|max:5120',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        $editedItems = null;

        if ($request->type === 'recheck') {
            $editedItems = json_decode($request->edited_items, true);

            if (! is_array($editedItems) || count($editedItems) === 0) {
                return response()->json(['message' => 'Ilagay muna kung alin ang mali sa grades mo.'], 422);
            }

            foreach ($editedItems as $item) {
                if (! isset($item['category'], $item['title'], $item['claimed_score'])) {
                    return response()->json(['message' => 'May kulang na detalye sa binagong item.'], 422);
                }
            }
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('grade-correction-attachments', 'supabase')
            : null;

        GradeCorrection::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'type' => $request->type,
            'period' => $request->period,
            'notes' => $request->notes,
            'edited_items' => $editedItems,
            'attachment_path' => $attachmentPath,
            'status' => $request->type === 'confirmed' ? 'resolved' : 'pending',
            'decision' => $request->type === 'confirmed' ? 'approved' : null,
            'resolved_at' => $request->type === 'confirmed' ? now() : null,
        ]);

        return response()->json([
            'message' => $request->type === 'confirmed'
                ? 'Salamat! Na-confirm na ang grades mo.'
                : 'Naipasa na ang recheck request mo, titingnan ito ni Sir Francisco.',
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
                'edited_items' => $c->edited_items, // [{category, title, claimed_score}, ...]
                'attachment_url' => $c->attachment_path
                    ? Storage::disk('supabase')->temporaryUrl($c->attachment_path, now()->addMinutes(30))
                    : null,
                'created_at' => $c->created_at,
                'resolved_at' => $c->resolved_at,
            ]);

        return Inertia::render('Admin/GradeCorrections/Index', [
            'corrections' => $corrections,
        ]);
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
}