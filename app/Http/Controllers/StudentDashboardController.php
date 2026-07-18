<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Section;
use App\Models\Student;
use App\Models\Topic;
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
}