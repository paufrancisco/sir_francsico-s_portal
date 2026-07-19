<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\GradesImport;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class GradeImportController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/Grades/Import');
    }

    public function store(Request $request, Section $section)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'period' => 'required|in:prelim,midterm,prefinal,finals',
        ]);

        Excel::import(new GradesImport($section->id, $request->input('period')), $request->file('file'));

        $section->update(['grades_computed_at' => now()]);

        return back()->with('success', 'Grades imported.');
    }
}