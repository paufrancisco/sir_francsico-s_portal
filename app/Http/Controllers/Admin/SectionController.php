<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Grade;

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
            'subject' => 'nullable|string|max:255',
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
            'gradeItems' => $this->gradeItems($section),
            'gradesBreakdown' => $this->gradesBreakdown($section),
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
            'gradeItems' => $this->gradeItems($section),
            'gradesBreakdown' => $this->gradesBreakdown($section),
        ]);
    }

    private function gradeItems(Section $section)
    {
        $categoryOrder = ['long_quiz' => 0, 'tp' => 1, 'exam' => 2];

        return Grade::where('section_id', $section->id)
            ->get(['category', 'title'])
            ->unique(fn ($g) => $g->category . '|' . $g->title)
            ->sortBy(fn ($g) => $categoryOrder[$g->category] ?? 99)
            ->values()
            ->map(fn ($g) => ['category' => $g->category, 'title' => $g->title]);
    }

    private function gradesBreakdown(Section $section)
    {
        $students = $section->students()->orderBy('full_name')->get();
        $allGrades = Grade::where('section_id', $section->id)->get();
        $items = $this->gradeItems($section);
        $weights = ['long_quiz' => 0.20, 'tp' => 0.30, 'exam' => 0.50];

        $rows = $students->map(function ($student) use ($allGrades, $items, $weights) {
            $studentGrades = $allGrades->where('student_id', $student->id);

            $scores = $items->mapWithKeys(function ($item) use ($studentGrades) {
                $g = $studentGrades->first(fn ($gr) => $gr->category === $item['category'] && $gr->title === $item['title']);
                return [
                    $item['category'] . '|' . $item['title'] => $g ? ['score' => $g->score, 'max_score' => $g->max_score] : null,
                ];
            });

            $weighted = 0;
            foreach ($weights as $category => $weight) {
                $catGrades = $studentGrades->where('category', $category);
                if ($catGrades->isEmpty()) {
                    continue;
                }
                $avgPercent = $catGrades->avg(fn ($g) => $g->max_score > 0 ? ($g->score / $g->max_score) * 100 : 0);
                $weighted += $avgPercent * $weight;
            }

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'scores' => $scores,
                'total_percentage' => round($weighted, 2),
            ];
        })
        ->sortByDesc('total_percentage')
        ->values();

        return $rows->map(function ($row, $i) {
            $row['rank'] = $i + 1;
            return $row;
        });
    }

    public function edit(Section $section)
    {
        return redirect()->route('admin.sections.index');
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sections,name,' . $section->id,
            'subject' => 'nullable|string|max:255',
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