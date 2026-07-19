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
        ]);

        // Burahin muna lahat ng existing grades ng section na ito
        // para full replace ang bawat import, hindi merge.
        Grade::where('section_id', $section->id)->delete();

        $import = new GradesImport($section->id);
        Excel::import($import, $request->file('file'));

        $section->update(['grades_computed_at' => now()]);

        $message = "{$import->importedCount} estudyante ang na-import ang grades.";

        if (! empty($import->skipped)) {
            $message .= " May " . count($import->skipped) . " na hindi na-match sa Masterlist: " . implode(', ', $import->skipped) . ".";
        }

        if (! empty($import->duplicates)) {
            $message .= " Babala: paulit-ulit sa file (huling row lang ang na-save): " . implode(', ', $import->duplicates) . ".";
        }

        return back()->with('success', $message);
    }
}