<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Section;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $sections = Section::withCount('students')->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'schedule' => $s->schedule,
            'students_count' => $s->students_count,
            'grades_computed' => ! is_null($s->grades_computed_at),
        ]);

        $latestAnnouncement = Announcement::with('section')->latest()->first();

        return Inertia::render('Admin/Dashboard', [
            'sections' => $sections,
            'latestAnnouncement' => $latestAnnouncement ? [
                'title' => $latestAnnouncement->title,
                'section_name' => $latestAnnouncement->section->name ?? null,
                'created_at' => $latestAnnouncement->created_at,
            ] : null,
        ]);
    }
}