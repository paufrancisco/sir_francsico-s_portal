<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Section;
use App\Models\Student;
use App\Models\Topic;
use Illuminate\Http\Request; 
use Inertia\Inertia;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $sections = Section::orderBy('name')->get(['id', 'name', 'subject', 'schedule']);

        $announcementsBySection = Announcement::latest()->get()->groupBy('section_id');
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
    public function verifyGrades(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        $weights = ['long_quiz' => 0.20, 'tp' => 0.30, 'exam' => 0.50];
        $categoryLabels = ['long_quiz' => 'Quizzes', 'tp' => 'Task Performance', 'exam' => 'Exams'];

        $grades = $student->grades;

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

        return response()->json([
            'name' => $student->full_name,
            'items' => $items,
            'scores' => $scores,
            'breakdown' => $breakdown,
            'total_percentage' => round($weighted, 2),
        ]);
    }
}