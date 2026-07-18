<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function forStudent(Student $student)
    {
        $grades = $student->grades()
            ->orderBy('subject')
            ->orderBy('category')
            ->get(['id', 'subject', 'category', 'title', 'score', 'max_score']);

        return response()->json(['grades' => $grades]);
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:' . $grade->max_score,
        ]);

        $grade->update(['score' => $validated['score']]);

        return response()->json(['grade' => $grade->fresh()]);
    }
}