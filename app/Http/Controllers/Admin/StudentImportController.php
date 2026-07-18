<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportController extends Controller
{
    public function create(Section $section)
    {
        return Inertia::render('Admin/Students/Import', [
            'section' => $section,
        ]);
    }

    public function store(Request $request, Section $section)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new StudentsImport($section->id);
        Excel::import($import, $request->file('file'));

        return Inertia::render('Admin/Students/ImportResult', [
            'imported' => $import->imported,
            'section' => $section,
        ]);
    }
}