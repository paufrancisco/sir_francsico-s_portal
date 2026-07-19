<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

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
            'photo_url' => $s->photo_url,
        ]);
    }

    public function updateStudent(Request $request, Section $section, Student $student)
    {
        $validated = $request->validate([
            'student_number' => 'required|string|max:255|unique:students,student_number,' . $student->id,
            'full_name' => 'required|string|max:255',
            'password' => 'nullable|string|min:4',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $student->update($validated);

        return back()->with('success', 'Na-update ang estudyante.');
    }

    public function destroyStudent(Section $section, Student $student)
    {
        $student->delete();

        return back()->with('success', 'Natanggal ang estudyante.');
    }

    public function destroyStudents(Request $request, Section $section)
    {
        $validated = $request->validate(['student_ids' => 'required|array']);

        Student::whereIn('id', $validated['student_ids'])
            ->where('section_id', $section->id)
            ->delete();

        return back()->with('success', count($validated['student_ids']) . ' estudyante ang natanggal.');
    }

    public function updatePhoto(Request $request, Section $section, Student $student)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        if ($student->photo_path) {
            Storage::disk('supabase')->delete($student->photo_path);
        }

        $path = $request->file('photo')->store('students', 'supabase');

        $student->update(['photo_path' => $path]);

        return back()->with('success', 'Na-update ang picture.');
    }

    public function deletePhoto(Section $section, Student $student)
    {
        if ($student->photo_path) {
            Storage::disk('supabase')->delete($student->photo_path);
            $student->update(['photo_path' => null]);
        }

        return back()->with('success', 'Naalis ang picture.');
    }

    public function importPhotos(Request $request, Section $section)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200',
        ]);

        $zipPath = $request->file('file')->getRealPath();
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return back()->with('error', 'Hindi mabuksan ang ZIP file.');
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $students = $section->students()->get()->keyBy(function ($s) {
            return strtolower(trim($s->student_number));
        });

        $matched = 0;
        $unmatched = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if (str_ends_with($entryName, '/') || str_contains($entryName, '__MACOSX')) {
                continue;
            }

            $ext = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                continue;
            }

            $studentNumber = strtolower(trim(pathinfo($entryName, PATHINFO_FILENAME)));
            $student = $students->get($studentNumber);

            if (!$student) {
                $unmatched[] = basename($entryName);
                continue;
            }

            $contents = $zip->getFromIndex($i);

            if ($student->photo_path) {
                Storage::disk('supabase')->delete($student->photo_path);
            }

            $newPath = 'students/' . $student->id . '-' . Str::random(8) . '.' . $ext;
            Storage::disk('supabase')->put($newPath, $contents, 'public');

            $student->update(['photo_path' => $newPath]);
            $matched++;
        }

        $zip->close();

        $message = "{$matched} na picture ang na-import.";
        if (count($unmatched) > 0) {
            $message .= ' Walang tumugma para sa: ' . implode(', ', array_slice($unmatched, 0, 10))
                . (count($unmatched) > 10 ? ' at ' . (count($unmatched) - 10) . ' pa...' : '');
        }

        return back()->with($matched > 0 ? 'success' : 'error', $message);
    }
}