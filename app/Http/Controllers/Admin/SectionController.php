<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SectionController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Sections/Index', [
            'sections' => Section::withCount('students')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.sections.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sections,name',
            'schedule' => 'nullable|string|max:255',
        ]);

        Section::create($validated);

        return back()->with('success', 'Section added.');
    }

    public function show(Section $section)
    {
        return Inertia::render('Admin/Sections/Show', [
            'section' => $section,
            'students' => $this->studentList($section),
            'revealed' => false,
        ]);
    }

    public function revealStudents(Request $request, Section $section)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Maling password.']);
        }

        return Inertia::render('Admin/Sections/Show', [
            'section' => $section,
            'students' => $this->studentList($section, withPassword: true),
            'revealed' => true,
        ]);
    }

    public function edit(Section $section)
    {
        return redirect()->route('admin.sections.index');
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sections,name,' . $section->id,
            'schedule' => 'nullable|string|max:255',
        ]);

        $section->update($validated);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return back()->with('success', 'Section deleted.');
    }

    private function studentList(Section $section, bool $withPassword = false)
    {
        return $section->students()->orderBy('full_name')->get()->map(fn ($s) => [
            'id' => $s->id,
            'student_number' => $s->student_number,
            'full_name' => $s->full_name,
            'password' => $withPassword ? $s->password : null,
        ]);
    }
}