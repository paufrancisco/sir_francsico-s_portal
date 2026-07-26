<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * Add a new topic under a section + period.
     * Modal fields: title, date_covered, has_quiz (+ quiz_items kung may quiz), has_tp
     */
    public function store(Request $request, Section $section)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'date_covered' => ['nullable', 'date'],
            'period' => ['required', 'in:prelim,midterm,prefinal,finals'],
            'has_quiz' => ['boolean'],
            'quiz_items' => ['nullable', 'required_if:has_quiz,true', 'integer', 'min:1'],
            'has_tp' => ['boolean'],
        ]);

        $lastOrder = Topic::where('section_id', $section->id)
            ->where('period', $data['period'])
            ->max('sort_order');

        $section->topics()->create([
            ...$data,
            'quiz_items' => $data['has_quiz'] ?? false ? $data['quiz_items'] : null,
            'sort_order' => ($lastOrder ?? 0) + 1,
        ]);

        return back()->with('flash', ['success' => 'Naidagdag ang topic.']);
    }

    /**
     * Edit modal — title, date, quiz details, TP flag.
     */
    public function updateDetails(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'date_covered' => ['nullable', 'date'],
            'has_quiz' => ['boolean'],
            'quiz_items' => ['nullable', 'required_if:has_quiz,true', 'integer', 'min:1'],
            'has_tp' => ['boolean'],
        ]);

        $topic->update([
            ...$data,
            'quiz_items' => $data['has_quiz'] ?? false ? $data['quiz_items'] : null,
        ]);

        return back()->with('flash', ['success' => 'Na-update ang topic.']);
    }

    /**
     * Soft-"delete" -> moves to Archive tab.
     */
    public function archive(Topic $topic)
    {
        $topic->update(['archived_at' => now()]);

        return back()->with('flash', ['success' => 'Na-archive ang topic.']);
    }

    /**
     * Bring back from Archive tab to the active list.
     */
    public function restore(Topic $topic)
    {
        $topic->update(['archived_at' => null]);

        return back()->with('flash', ['success' => 'Na-restore ang topic.']);
    }

    /**
     * Permanent delete — only exposed inside the Archive view.
     */
    public function destroy(Topic $topic)
    {
        $topic->delete();

        return back()->with('flash', ['success' => 'Permanenteng natanggal ang topic.']);
    }
}
