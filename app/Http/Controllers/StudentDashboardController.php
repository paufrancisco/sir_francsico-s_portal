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
        $rankPeriods = ['prelim', 'midterm', 'prefinal', 'finals'];

        foreach ($sections as $section) {
            $students = Student::whereHas('sections', fn ($q) => $q->where('sections.id', $section->id))->with('grades')->get();

            foreach ($rankPeriods as $rankPeriod) {
                $allRanked = $students->map(function ($student) use ($weights, $section, $rankPeriod) {
                    $periodGrades = $student->grades
                        ->where('section_id', $section->id)
                        ->where('period', $rankPeriod);

                    // walang grades sa period na ito = hindi isasama sa ranking
                    if ($periodGrades->isEmpty()) {
                        return null;
                    }

                    $weighted = 0;
                    foreach ($weights as $category => $weight) {
                        $catGrades = $periodGrades->where('category', $category);
                        if ($catGrades->isEmpty()) {
                            continue;
                        }
                        $avgPercent = $catGrades->avg(
                            fn ($g) => $g->max_score > 0 ? ($g->score / $g->max_score) * 100 : 0
                        );
                        $weighted += $avgPercent * $weight;
                    }

                    return [
                        'id' => $student->id,
                        'name' => $student->full_name,
                        'grade' => round($weighted, 2),
                        'photo_url' => $student->photo_url,
                        'frame' => $student->frame,
                    ];
                })
                ->filter()
                ->sortByDesc('grade')
                ->values();

                $gradeList = $allRanked->pluck('grade');
                $allRanked = $allRanked->map(function ($row) use ($gradeList) {
                    $row['rank'] = $gradeList->filter(fn ($g) => $g > $row['grade'])->unique()->count() + 1;
                    return $row;
                });

                // rank 1-10 (kasama ang ties)
                $top10BySection[$section->id][$rankPeriod] = $allRanked->filter(fn ($row) => $row['rank'] <= 10)->values();
            }
        }

        return Inertia::render('Student/Dashboard', [
            'defaultPeriod' => $this->defaultPeriod(),
            'sections' => $sections,
            'announcementsBySection' => $announcementsBySection,
            'topicsBySection' => $topicsBySection,
            'globalCalendarEvents' => $globalCalendarEvents,
            'calendarEventsBySection' => $calendarEventsBySection,
            'top10BySection' => $top10BySection,
            'lastCalendarUpdate' => $allEvents->max('updated_at'),
        ]);
    }

    // reminder: default period na bubungad sa students (galing sa admin setting)
    private function defaultPeriod(): string
    {
        $period = Setting::get('student_default_period', 'prelim');

        return in_array($period, ['prelim', 'midterm', 'prefinal', 'finals'], true) ? $period : 'prelim';
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
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        if ($request->new_password === $request->current_password) {
            return response()->json(['message' => 'New password must be different from the old one.'], 422);
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
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        if (is_null($student->password_changed_at)) {
            return response()->json(['must_change_password' => true]);
        }

        $period = in_array($request->period, ['prelim', 'midterm', 'prefinal', 'finals'], true)
            ? $request->period
            : $this->defaultPeriod();

        $weights = ['long_quiz' => 0.20, 'tp' => 0.30, 'exam' => 0.50];
        $categoryLabels = ['long_quiz' => 'Quizzes', 'tp' => 'Task Performance', 'exam' => 'Exams'];

        $categoryOrder = ['long_quiz' => 0, 'tp' => 1, 'exam' => 2];
        $deadline = Setting::get('grade_correction_deadline');

        $sections = $student->sections()
            ->orderBy('name')
            ->get(['sections.id', 'sections.name', 'sections.subject']);

        $result = $sections->map(function ($section) use ($student, $period, $weights, $categoryLabels, $categoryOrder) {
            $grades = $student->grades()
                ->where('section_id', $section->id)
                ->where('period', $period)
                ->get();

            $items = $grades
                ->sortBy(fn ($g) => $categoryOrder[$g->category] ?? 99)
                ->values()
                ->map(fn ($g) => [
                    'category' => $g->category,
                    'title' => $g->title,
                    'score' => $g->score,
                    'max_score' => $g->max_score,
                ]);

            $weighted = 0;
            $breakdown = [];
            foreach ($weights as $category => $weight) {
                $cat = $grades->where('category', $category);
                $max = $cat->sum('max_score');

                // sum-based, kapareho ng admin GradesTab
                $percent = $max > 0 ? ($cat->sum('score') / $max) * 100 : null;
                $contribution = ($percent ?? 0) * $weight;
                $weighted += $contribution;

                $breakdown[] = [
                    'category' => $category,
                    'label' => $categoryLabels[$category],
                    'weight_percent' => $weight * 100,
                    'avg_percent' => $percent !== null ? round($percent, 2) : null,
                    'contribution' => round($contribution, 2),
                ];
            }

            $pending = GradeCorrection::where('student_id', $student->id)
                ->where('section_id', $section->id)
                ->where('period', $period)
                ->where('type', 'recheck')
                ->where('status', 'pending')
                ->latest()
                ->first();

            $resolved = GradeCorrection::where('student_id', $student->id)
                ->where('section_id', $section->id)
                ->where('period', $period)
                ->where('type', 'recheck')
                ->where('status', 'resolved')
                ->latest('resolved_at')
                ->first();

            return [
                'section_id' => $section->id,
                'subject' => $section->subject,
                'section_name' => $section->name,
                'items' => $items,
                'breakdown' => $breakdown,
                'total_percentage' => round($weighted, 2),
                'has_grades' => $grades->isNotEmpty(),
                'pending_correction' => $pending ? [
                    'id' => $pending->id,
                    'notes' => $pending->notes,
                    'edited_items' => $pending->edited_items,
                    'attachment_url' => $pending->attachment_path
                        ? Storage::disk('supabase')->temporaryUrl($pending->attachment_path, now()->addMinutes(30))
                        : null,
                    'created_at' => $pending->created_at,
                ] : null,
                'resolved_correction' => $resolved ? [
                    'id' => $resolved->id,
                    'decision' => $resolved->decision,
                    'review_comment' => $resolved->review_comment,
                    'review_attachment_url' => $resolved->review_attachment_path
                        ? Storage::disk('supabase')->temporaryUrl($resolved->review_attachment_path, now()->addMinutes(30))
                        : null,
                    'resolved_at' => $resolved->resolved_at,
                ] : null,
            ];
        })->values();

        // TEMPORARY compat: ibalik pa rin ang lumang shape (unang subject lang)
        // para gumana ang kasalukuyang modal hanggang ma-update ang Dashboard.vue.
        $first = $result->first();

        return response()->json([
            'name' => $student->full_name,
            'photo_url' => $student->photo_url,
            'period' => $period,
            'sections' => $result,
            'correction_deadline' => $deadline,
            'correction_locked' => $deadline ? now()->gt(\Carbon\Carbon::parse($deadline)->endOfDay()) : false,

            'items' => $first ? $first['items']->map(fn ($i) => ['category' => $i['category'], 'title' => $i['title']])->values() : [],
            'scores' => $first ? $first['items']->mapWithKeys(fn ($i) => [
                $i['category'] . '|' . $i['title'] => ['score' => $i['score'], 'max_score' => $i['max_score']],
            ]) : [],
            'breakdown' => $first['breakdown'] ?? [],
            'total_percentage' => $first['total_percentage'] ?? 0,
            'pending_correction' => $first['pending_correction'] ?? null,
            'resolved_correction' => $first['resolved_correction'] ?? null,
        ]);
    }
}