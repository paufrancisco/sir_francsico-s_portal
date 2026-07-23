<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Section;
use App\Models\Student;
use App\Models\Topic;
use Illuminate\Http\Request; 
use Inertia\Inertia;
use App\Models\Setting;
use App\Models\GradeCorrection;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $sections = Section::orderBy('name')->get(['id', 'name', 'subject', 'schedule']);

        $allAnnouncements = Announcement::with('sections')->latest()->get();
        $globalAnnouncements = $allAnnouncements->where('is_global', true)->values();

        $announcementsBySection = [];
        foreach ($sections as $section) {
            $specific = $allAnnouncements->filter(
                fn ($a) => ! $a->is_global && $a->sections->contains('id', $section->id)
            )->values();

            $announcementsBySection[$section->id] = $globalAnnouncements
                ->merge($specific)
                ->sortByDesc('created_at')
                ->values();
        }
        $topicsBySection = Topic::orderBy('date_covered')->get()->groupBy('section_id');

        $allEvents = CalendarEvent::orderBy('event_date')->get();
        $globalCalendarEvents = $allEvents->whereNull('section_id')->values();
        $calendarEventsBySection = $allEvents->whereNotNull('section_id')->groupBy('section_id');

        $weights = ['long_quiz' => 0.20, 'tp' => 0.30, 'exam' => 0.50];

        $top10BySection = [];
        foreach ($sections as $section) {
            $students = Student::where('section_id', $section->id)->with('grades')->get();

            $ranked = $students->map(function ($student) use ($weights) {
                $weighted = 0;

                foreach ($weights as $category => $weight) {
                    $catGrades = $student->grades->where('category', $category);
                    if ($catGrades->isEmpty()) {
                        continue;
                    }
                    $avgPercent = $catGrades->avg(
                        fn ($g) => $g->max_score > 0 ? ($g->score / $g->max_score) * 100 : 0
                    );
                    $weighted += $avgPercent * $weight;
                }

                return [
                    'name' => $student->full_name,
                    'grade' => round($weighted, 2),
                    'photo_url' => $student->photo_url,
                ];
            })
            ->sortByDesc('grade')
            ->take(10)
            ->values();

            $top10BySection[$section->id] = $ranked;
        }

        return Inertia::render('Student/Dashboard', [
            'sections' => $sections,
            'announcementsBySection' => $announcementsBySection,
            'topicsBySection' => $topicsBySection,
            'globalCalendarEvents' => $globalCalendarEvents,
            'calendarEventsBySection' => $calendarEventsBySection,
            'top10BySection' => $top10BySection,
            'lastCalendarUpdate' => $allEvents->max('updated_at'),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->current_password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        if ($request->new_password === $request->current_password) {
            return response()->json(['message' => 'Ibahin mo ang bagong password sa luma.'], 422);
        }

        $student->update([
            'password' => $request->new_password,
            'password_changed_at' => now(),
        ]);

        // Diretsong ibalik ang grades gamit ang bagong password, parang ni-verify ulit
        $request->merge(['password' => $request->new_password]);
        return $this->verifyGrades($request);
    }

    public function verifyGrades(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
            'period' => 'nullable|in:prelim,midterm,prefinal,finals',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        if (is_null($student->password_changed_at)) {
            return response()->json(['must_change_password' => true]);
        }

        $period = in_array($request->period, ['prelim', 'midterm', 'prefinal', 'finals'], true)
            ? $request->period
            : 'prelim';

        $weights = ['long_quiz' => 0.20, 'tp' => 0.30, 'exam' => 0.50];
        $categoryLabels = ['long_quiz' => 'Quizzes', 'tp' => 'Task Performance', 'exam' => 'Exams'];

        $grades = $student->grades()->where('period', $period)->get();

        $categoryOrder = ['long_quiz' => 0, 'tp' => 1, 'exam' => 2];

        $items = $grades
            ->unique(fn ($g) => $g->category . '|' . $g->title)
            ->sortBy(fn ($g) => $categoryOrder[$g->category] ?? 99)
            ->values()
            ->map(fn ($g) => ['category' => $g->category, 'title' => $g->title]);

        $scores = $items->mapWithKeys(function ($item) use ($grades) {
            $g = $grades->first(fn ($gr) => $gr->category === $item['category'] && $gr->title === $item['title']);
            return [
                $item['category'] . '|' . $item['title'] => $g
                    ? ['score' => $g->score, 'max_score' => $g->max_score]
                    : null,
            ];
        });

        $weighted = 0;
        $breakdown = [];

        foreach ($weights as $category => $weight) {
            $catGrades = $grades->where('category', $category);
            $weightPercent = $weight * 100;

            if ($catGrades->isEmpty()) {
                $breakdown[] = [
                    'category' => $category,
                    'label' => $categoryLabels[$category],
                    'weight_percent' => $weightPercent,
                    'avg_percent' => null,
                    'contribution' => 0,
                ];
                continue;
            }

            $avgPercent = $catGrades->avg(fn ($g) => $g->max_score > 0 ? ($g->score / $g->max_score) * 100 : 0);
            $contribution = $avgPercent * $weight;
            $weighted += $contribution;

            $breakdown[] = [
                'category' => $category,
                'label' => $categoryLabels[$category],
                'weight_percent' => $weightPercent,
                'avg_percent' => round($avgPercent, 2),
                'contribution' => round($contribution, 2),
            ];
        }

        $pendingCorrection = GradeCorrection::where('student_id', $student->id)
            ->where('section_id', $student->section_id)
            ->where('period', $period)
            ->where('type', 'recheck')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $deadline = Setting::get('grade_correction_deadline');

        return response()->json([
            'name' => $student->full_name,
            'period' => $period,
            'items' => $items,
            'scores' => $scores,
            'breakdown' => $breakdown,
            'total_percentage' => round($weighted, 2),
            'correction_deadline' => $deadline,
            'correction_locked' => $deadline ? now()->gt(\Carbon\Carbon::parse($deadline)->endOfDay()) : false,
            'pending_correction' => $pendingCorrection ? [
                'id' => $pendingCorrection->id,
                'notes' => $pendingCorrection->notes,
                'edited_items' => $pendingCorrection->edited_items,
                'attachment_url' => $pendingCorrection->attachment_path
                    ? Storage::disk('supabase')->temporaryUrl($pendingCorrection->attachment_path, now()->addMinutes(30))
                    : null,
                'created_at' => $pendingCorrection->created_at,
            ] : null,
        ]);
    }
}