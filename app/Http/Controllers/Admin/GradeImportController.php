<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\GradesImport;
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

        // Hindi na kailangan pumili ng period - basahin natin ang buong
        // "Input" sheet ng STI P60 template, na naglalaman na ng lahat
        // ng 4 na period (Prelim, Midterm, Pre-Final, Finals) sabay-sabay.
        $import = new GradesImport($section->id);
        Excel::import($import, $request->file('file'));

        $section->update(['grades_computed_at' => now()]);

        $message = "{$import->importedCount} estudyante ang na-import ang grades (Prelim, Midterm, Pre-Final, Finals).";

        if (! empty($import->skipped)) {
            $message .= " May " . count($import->skipped) . " na hindi na-match sa Masterlist: " . implode(', ', $import->skipped) . ".";
        }

        if (! empty($import->duplicates)) {
            $message .= " Babala: paulit-ulit sa file (huling row lang ang na-save): " . implode(', ', $import->duplicates) . ".";
        }

        return redirect()
            ->route('admin.sections.show', ['section' => $section])
            ->with('success', $message);
    }
}