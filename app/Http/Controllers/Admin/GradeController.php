<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function forStudentInSection(Request $request, Section $section, Student $student)
    {
        $period = $request->query('period', 'prelim');
        $categoryOrder = ['long_quiz' => 0, 'tp' => 1, 'exam' => 2];

        // Lahat ng grade items na na-define sa section para dito lang sa period na 'to
        $items = Grade::where('section_id', $section->id)
            ->where('period', $period)
            ->get(['category', 'title', 'max_score'])
            ->unique(fn ($g) => $g->category . '|' . $g->title)
            ->sortBy(fn ($g) => $categoryOrder[$g->category] ?? 99)
            ->values();

        $studentGrades = Grade::where('section_id', $section->id)
            ->where('student_id', $student->id)
            ->where('period', $period)
            ->get();

        $merged = $items->map(function ($item) use ($studentGrades, $section, $student, $period) {
            $existing = $studentGrades->first(
                fn ($g) => $g->category === $item->category && $g->title === $item->title
            );

            return [
                'id' => $existing?->id,
                'student_id' => $student->id,
                'section_id' => $section->id,
                'category' => $item->category,
                'period' => $period,
                'title' => $item->title,
                'score' => $existing?->score,
                'max_score' => $item->max_score,
            ];
        });

        return response()->json(['grades' => $merged]);
    }

    public function forStudent(Student $student)
    {
        $grades = $student->grades()
            ->orderBy('period')
            ->orderBy('subject')
            ->orderBy('category')
            ->get(['id', 'subject', 'category', 'period', 'title', 'score', 'max_score']);

        return response()->json(['grades' => $grades]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'section_id' => 'required|exists:sections,id',
            'category' => 'required|in:long_quiz,tp,exam',
            'period' => 'required|in:prelim,midterm,prefinal,finals',
            'title' => 'required|string',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:0.01',
        ]);

        $grade = Grade::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'section_id' => $validated['section_id'],
                'category' => $validated['category'],
                'period' => $validated['period'],
                'title' => $validated['title'],
            ],
            [
                'score' => $validated['score'],
                'max_score' => $validated['max_score'],
                'recorded_by' => auth()->id(),
            ]
        );

        return response()->json(['grade' => $grade]);
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:' . $grade->max_score,
        ]);

        $grade->update(['score' => $validated['score']]);

        return response()->json(['grade' => $grade->fresh()]);
    }

    public function destroyForStudent(Request $request, Section $section, Student $student)
    {
        Grade::where('section_id', $section->id)
            ->where('student_id', $student->id)
            ->delete();

        return back()->with('success', 'Natanggal na ang grades ni ' . $student->full_name . '.');
    }

    public function destroyForStudents(Request $request, Section $section)
    {
        $validated = $request->validate(['student_ids' => 'required|array']);

        Grade::where('section_id', $section->id)
            ->whereIn('student_id', $validated['student_ids'])
            ->delete();

        return back()->with('success', count($validated['student_ids']) . ' estudyante ang na-clear ang grades.');
    }
}