<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Grade;
use App\Models\GradeCorrection;
use App\Models\Section;
use App\Models\StudentActivity;
use App\Models\StudentLoginLog;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $sections = Section::withCount('students')->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'subject' => $s->subject,
            'schedule' => $s->schedule,
            'students_count' => $s->students_count,
            'grades_computed' => ! is_null($s->grades_computed_at),
        ]);

        $announcements = Announcement::with('sections')->latest()->take(5)->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'body' => $a->body,
            'is_global' => $a->is_global,
            'section_ids' => $a->sections->pluck('id'),
            'target' => $a->is_global ? 'All sections' : $a->sections->pluck('name')->implode(', '),
            'created_at' => $a->created_at,
        ]);

        $periodLabels = ['prelim' => 'Prelim', 'midterm' => 'Midterm', 'prefinal' => 'Prefinals', 'finals' => 'Finals'];
        $sectionsWithGrades = Grade::query()
            ->whereNotNull('period')
            ->select('period', 'section_id')
            ->distinct()
            ->get()
            ->groupBy('period');

        // A period counts as done for a section once it has at least one grade
        $periodProgress = collect($periodLabels)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'done' => $sectionsWithGrades->get($key, collect())->count(),
            'total' => $sections->count(),
        ])->values();

        // Same rule as the section page: the latest record per student decides the status.
        $statusOf = function ($r) {
            if ($r->type === 'confirmed') {
                return 'confirmed';
            }
            if ($r->status === 'pending') {
                return 'pending';
            }
            if ($r->decision === 'rejected') {
                return 'rejected';
            }

            return null; // approved rechecks are not counted
        };

        $statusByPeriod = GradeCorrection::whereNotNull('period')
            ->where('archived', false)
            ->latest()->latest('id')
            ->get()
            ->groupBy('period')
            ->map(fn ($rows) => $rows->unique('student_id')->map($statusOf)->filter()->countBy());

        $periodProgress = $periodProgress->map(function ($p) use ($statusByPeriod) {
            $counts = $statusByPeriod->get($p['key'], collect());

            return $p + [
                'confirmed' => $counts->get('confirmed', 0),
                'pending' => $counts->get('pending', 0),
                'rejected' => $counts->get('rejected', 0),
            ];
        })->values();

        $recentLogins = StudentLoginLog::with('student.section')
            ->latest('created_at')->latest('id')->take(5)->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->student?->full_name ?? $l->student_number,
                'section' => $l->student?->section?->name,
                'source' => $l->source,
                'success' => $l->success,
                'created_at' => $l->created_at,
            ]);

        $recentActivity = StudentActivity::with('student.section')
            ->latest('created_at')->latest('id')->take(5)->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->student?->full_name ?? 'Unknown student',
                'section' => $a->student?->section?->name,
                'type' => $a->type,
                'summary' => $a->summary,
                'created_at' => $a->created_at,
            ]);

        // Latest record per student (any period) decides the status.
        $statusBySection = GradeCorrection::whereNotNull('section_id')
            ->where('archived', false)
            ->latest()->latest('id')
            ->get()
            ->groupBy('section_id')
            ->map(fn ($rows) => $rows->unique('student_id')->map($statusOf)->filter()->countBy());

        $confirmations = $sections->map(function ($s) use ($statusBySection) {
            $counts = $statusBySection->get($s['id'], collect());

            return [
                'id' => $s['id'],
                'name' => $s['name'],
                'confirmed' => $counts->get('confirmed', 0),
                'pending' => $counts->get('pending', 0),
                'rejected' => $counts->get('rejected', 0),
                'total' => $s['students_count'],
            ];
        })->values();

        return Inertia::render('Admin/Dashboard', [
            'sections' => $sections,
            'announcements' => $announcements,
            'allSections' => Section::orderBy('name')->get(['id', 'name', 'subject']),
            'periodProgress' => $periodProgress,
            'confirmations' => $confirmations,
            'recentLogins' => $recentLogins,
            'recentActivity' => $recentActivity,
        ]);
    }
}