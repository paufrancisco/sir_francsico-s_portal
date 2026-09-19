<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Grade;
use App\Models\Student;
use App\Models\GradeCorrection;
use App\Models\Seat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use App\Models\Topic;
class SectionController extends Controller
{
    private const PERIODS = ['prelim', 'midterm', 'prefinal', 'finals'];
    private const LAYOUTS = ['lecture', 'comlab'];

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
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
        ]);

        Section::create($validated);

        return back()->with('success', 'Section added.');
    }

    public function show(Request $request, Section $section)
    {
        $period = $this->resolvePeriod($request->query('period'));
        $layout = $this->resolveLayout($request->query('layout'));

        return Inertia::render('Admin/Sections/Show', [
            'section' => $section,
            'students' => $this->studentList($section, withPassword: session()->has('revealed_section_' . $section->id)),
            'revealed' => session()->pull('revealed_section_' . $section->id, false),
            'gradeItems' => $this->gradeItems($section, $period),
            'gradesBreakdown' => $this->gradesBreakdown($section, $period),
            'currentPeriod' => $period,
            'periods' => self::PERIODS,
            'topics' => Topic::where('section_id', $section->id)
                ->period($period)
                ->active()
                ->orderBy('sort_order')
                ->get(),
            'archivedTopics' => Topic::where('section_id', $section->id)
                ->period($period)
                ->archived()
                ->orderByDesc('archived_at')
                ->get(),
            'currentLayout' => $layout,
            'seats' => $this->seatsForLayout($section, $layout),
            'unassignedStudents' => $this->unassignedStudents($section, $layout),
        ]);
    }

    public function revealStudents(Request $request, Section $section)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session(['revealed_section_' . $section->id => true]);

        return redirect()->route('admin.sections.show', $section);
    }

    private function resolvePeriod(?string $period): string
    {
        return in_array($period, self::PERIODS, true) ? $period : 'prelim';
    }

    private function resolveLayout(?string $layout): string
    {
        return in_array($layout, self::LAYOUTS, true) ? $layout : 'lecture';
    }

    private function seatsForLayout(Section $section, string $layout)
    {
        $seats = Seat::where('section_id', $section->id)
            ->where('layout', $layout)
            ->with('student:id,student_number,full_name,aura_points,photo_path')
            ->get()
            ->keyBy('position_x');

        return $seats->mapWithKeys(fn ($seat, $pos) => [$pos => [
            'student_id' => $seat->student_id,
            'student' => $seat->student ? [
                'id' => $seat->student->id,
                'student_number' => $seat->student->student_number,
                'full_name' => $seat->student->full_name,
                'aura_points' => $seat->student->aura_points,
                'photo_url' => $seat->student->photo_url,
            ] : null,
        ]]);
    }

    private function unassignedStudents(Section $section, string $layout)
    {
        $students = $section->students()->orderBy('full_name')->get(['id', 'student_number', 'full_name', 'aura_points', 'photo_path']);

        $assignedStudentIds = Seat::where('section_id', $section->id)
            ->where('layout', $layout)
            ->pluck('student_id')
            ->filter()
            ->values();

        return $students->whereNotIn('id', $assignedStudentIds)->values()->map(fn ($s) => [
            'id' => $s->id,
            'student_number' => $s->student_number,
            'full_name' => $s->full_name,
            'aura_points' => $s->aura_points,
            'photo_url' => $s->photo_url,
        ]);
    }

    private function gradeItems(Section $section, string $period)
    {
        // Same as Excel: PT/Lab, Quiz, Exam
        $categoryOrder = ['tp' => 0, 'long_quiz' => 1, 'exam' => 2];

        return Grade::where('section_id', $section->id)
            ->where('period', $period)
            ->get(['category', 'title'])
            ->unique(fn ($g) => $g->category . '|' . $g->title)
            ->sortBy(fn ($g) => ($categoryOrder[$g->category] ?? 9) . '|' . $g->title)
            ->values()
            ->map(fn ($g) => ['category' => $g->category, 'title' => $g->title]);
    }

    /**
     * Transmutation table (same as the TRANSMUTATION sheet in Excel).
     */
    private function transmute(float $grade): float
    {
        $table = [
            97.5 => 1.0,
            94.5 => 1.25,
            91.5 => 1.5,
            86.5 => 1.75,
            81.5 => 2.0,
            76.0 => 2.25,
            70.5 => 2.5,
            65.0 => 2.75,
            59.5 => 3.0,
        ];

        foreach ($table as $min => $eq) {
            if ($grade >= $min) {
                return $eq;
            }
        }

        return 5.0;
    }

    private function gradesBreakdown(Section $section, string $period)
    {
        $students = $section->students()->orderBy('full_name')->get();
        $allGrades = Grade::where('section_id', $section->id)->where('period', $period)->get();
        $items = $this->gradeItems($section, $period);
        $weights = ['long_quiz' => 0.20, 'tp' => 0.30, 'exam' => 0.50];

        // Most recent correction record (confirmed OR recheck) per student, scoped to this section+period.
        // A student's latest action — whether they confirmed or requested a recheck — is what the
        // Status column reflects.
        $corrections = GradeCorrection::where('section_id', $section->id)
            ->where('period', $period)
            ->latest()
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        $rows = $students->map(function ($student) use ($allGrades, $items, $weights, $corrections) {
            $studentGrades = $allGrades->where('student_id', $student->id);

            $find = fn ($item) => $studentGrades->first(
                fn ($gr) => $gr->category === $item['category'] && $gr->title === $item['title']
            );

            $scores = $items->mapWithKeys(function ($item) use ($find) {
                $g = $find($item);
                return [
                    $item['category'] . '|' . $item['title'] => $g ? ['score' => $g->score, 'max_score' => $g->max_score] : null,
                ];
            });

            $weighted = 0;
            $categoryPercentages = [];
            $categoryTotals = [];

            foreach ($weights as $category => $weight) {
                $catItems = $items->where('category', $category);

                if ($catItems->isEmpty()) {
                    $categoryPercentages[$category] = null;
                    $categoryTotals[$category] = null;
                    continue; // no item defined for this category, for any student
                }

                $avgPercent = $catItems->map(function ($item) use ($find) {
                    $g = $find($item);
                    if (! $g) {
                        return 0; // missing = 0
                    }
                    return $g->max_score > 0 ? ($g->score / $g->max_score) * 100 : 0;
                })->avg();

                $categoryPercentages[$category] = round($avgPercent, 2);
                // TTL = raw score total for the category (PT/Lab TTL, Quiz TTL)
                $categoryTotals[$category] = round(
                    $catItems->sum(fn ($item) => optional($find($item))->score ?? 0),
                    2
                );
                $weighted += $avgPercent * $weight;
            }

            $total = round($weighted, 2);
            $hasGrades = $studentGrades->isNotEmpty();
            $correction = $corrections->get($student->id);

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_number' => $student->student_number,
                'scores' => $scores,
                'category_percentages' => $categoryPercentages,
                'category_totals' => $categoryTotals,
                'total_percentage' => $total,
                'equivalent' => $hasGrades ? $this->transmute($total) : null,
                'pending_correction' => $correction ? [
                    'id' => $correction->id,
                    'type' => $correction->type,
                    'status' => $correction->status,
                    'decision' => $correction->decision,
                    'notes' => $correction->notes,
                    'edited_items' => $correction->edited_items,
                    'attachment_url' => $correction->attachment_path
                        ? Storage::disk('supabase')->temporaryUrl($correction->attachment_path, now()->addMinutes(30))
                        : null,
                ] : null,
            ];
        })
        ->sortByDesc('total_percentage')
        ->values();

        // Rank ties, same as RANK() in Excel
        $totals = $rows->pluck('total_percentage');

        return $rows->map(function ($row) use ($totals) {
            $row['rank'] = $totals->filter(fn ($t) => $t > $row['total_percentage'])->unique()->count() + 1;
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
            'name' => 'required|string|max:255',
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

    public function storeStudent(Request $request, Section $section)
    {
        $validated = $request->validate([
            'student_number' => 'required|string|max:255|unique:students,student_number',
            'full_name' => 'required|string|max:255',
            'password' => 'required|string|min:4',
        ]);

        $section->students()->create($validated);

        return back()->with('success', 'Student added.');
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

        return back()->with('success', 'Student updated.');
    }

    public function destroyStudent(Section $section, Student $student)
    {
        $student->delete();

        return back()->with('success', 'Student removed.');
    }

    public function destroyStudents(Request $request, Section $section)
    {
        $validated = $request->validate(['student_ids' => 'required|array']);

        Student::whereIn('id', $validated['student_ids'])
            ->where('section_id', $section->id)
            ->delete();

        return back()->with('success', count($validated['student_ids']) . ' student(s) removed.');
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

        if ($request->wantsJson()) {
            return response()->json(['photo_url' => $student->fresh()->photo_url]);
        }

        return back()->with('success', 'Photo updated.');
    }

    public function deletePhoto(Section $section, Student $student)
    {
        if ($student->photo_path) {
            Storage::disk('supabase')->delete($student->photo_path);
            $student->update(['photo_path' => null]);
        }

        return back()->with('success', 'Photo removed.');
    }

    public function importPhotos(Request $request, Section $section)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200',
        ]);

        $zipPath = $request->file('file')->getRealPath();
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return back()->with('error', 'Could not open the ZIP file.');
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

        $message = "{$matched} photo(s) imported.";
        if (count($unmatched) > 0) {
            $message .= ' No match found for: ' . implode(', ', array_slice($unmatched, 0, 10))
                . (count($unmatched) > 10 ? ' and ' . (count($unmatched) - 10) . ' more...' : '');
        }

        return back()->with($matched > 0 ? 'success' : 'error', $message);
    }
}