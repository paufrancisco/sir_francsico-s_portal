<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Announcements/Index', [
            'announcements' => Announcement::with('sections')->latest()->get(),
            'sections' => Section::orderBy('name')->get(['id', 'name', 'subject']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_global' => 'required|boolean',
            'section_ids' => 'required_if:is_global,false|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_global' => $validated['is_global'],
            'posted_by' => auth()->id(),
        ]);

        if (! $validated['is_global']) {
            $announcement->sections()->sync($validated['section_ids']);
        }

        return back()->with('success', 'Announcement posted.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_global' => 'required|boolean',
            'section_ids' => 'required_if:is_global,false|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_global' => $validated['is_global'],
        ]);

        $announcement->sections()->sync($validated['is_global'] ? [] : $validated['section_ids']);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }
}