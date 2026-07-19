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
            'target' => $a->is_global ? 'Lahat ng section' : $a->sections->pluck('name')->implode(', '),
            'created_at' => $a->created_at,
        ]);

        return Inertia::render('Admin/Dashboard', [
            'sections' => $sections,
            'announcements' => $announcements,
            'allSections' => Section::orderBy('name')->get(['id', 'name', 'subject']),
        ]);
    }
}