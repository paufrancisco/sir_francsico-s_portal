<?php

namespace App\Http\Controllers;

use App\Models\GradeCorrection;
use App\Models\Student;
use Illuminate\Http\Request;
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
            'notes' => 'nullable|string|max:1000',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        if ($request->type === 'recheck' && ! $request->notes) {
            return response()->json(['message' => 'Ilagay muna kung ano ang mali sa grades mo.'], 422);
        }

        GradeCorrection::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'type' => $request->type,
            'notes' => $request->notes,
            'status' => $request->type === 'confirmed' ? 'resolved' : 'pending',
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
                'created_at' => $c->created_at,
                'resolved_at' => $c->resolved_at,
            ]);

        return Inertia::render('Admin/GradeCorrections/Index', [
            'corrections' => $corrections,
        ]);
    }

    public function resolve(GradeCorrection $gradeCorrection)
    {
        $gradeCorrection->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Na-mark as resolved.');
    }
}