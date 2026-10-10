<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassmateMessage;
use App\Models\Section;
use App\Models\StudentActivity;
use App\Models\StudentLoginLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['messages', 'activity'], true)
            ? $request->query('tab')
            : 'logins';
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('from');
        $to = $request->query('to');
        $type = $request->query('type');
        $sectionId = $request->query('section_id');
        $unreadOnly = $request->query('unread') === '1' ? '1' : null;
        $sections = [];

        $like = fn ($builder, string $column, string $term, bool $or = false) => $builder->{$or ? 'orWhereRaw' : 'whereRaw'}(
            "LOWER({$column}) LIKE ?",
            ['%' . mb_strtolower($term) . '%']
        );

        if ($tab === 'logins') {
            $query = StudentLoginLog::with('student.section')->latest('created_at')->latest('id');

            if ($q !== '') {
                $query->where(function ($w) use ($q, $like) {
                    $like($w, 'student_login_logs.student_number', $q);
                    $w->orWhereHas('student', fn ($s) => $like($s, 'students.full_name', $q));
                });
            }
            if ($request->query('result') === 'failed') $query->where('success', false);
            if ($request->query('result') === 'success') $query->where('success', true);
            if ($from) $query->whereDate('created_at', '>=', $from);
            if ($to) $query->whereDate('created_at', '<=', $to);

            $rows = $query->paginate(25)->withQueryString()->through(fn ($l) => [
                'id' => $l->id,
                'student_name' => $l->student?->full_name,
                'student_number' => $l->student_number,
                'section' => $l->student?->section?->name,
                'source' => $l->source,
                'success' => $l->success,
                'ip' => $l->ip,
                'user_agent' => $l->user_agent,
                'at' => $l->created_at?->toIso8601String(),
            ]);
        } elseif ($tab === 'messages') {
            $query = ClassmateMessage::with('sender.section')->latest('created_at')->latest('id');

            if ($q !== '') {
                $query->where(function ($w) use ($q, $like) {
                    $like($w, 'classmate_messages.body', $q);
                    $w->orWhereHas('sender', function ($s) use ($q, $like) {
                        $s->where(function ($x) use ($q, $like) {
                            $like($x, 'students.full_name', $q);
                            $like($x, 'students.student_number', $q, true);
                        });
                    });
                });
            }
            if ($from) $query->whereDate('created_at', '>=', $from);
            if ($to) $query->whereDate('created_at', '<=', $to);

            $rows = $query->paginate(25)->withQueryString()->through(fn ($m) => [
                'id' => $m->id,
                'student_name' => $m->sender?->full_name,
                'student_number' => $m->sender?->student_number,
                'section' => $m->sender?->section?->name,
                'body' => $m->body,
                'hidden' => (bool) $m->hidden_at,
                'at' => $m->created_at?->toIso8601String(),
            ]);
        } else {
            $query = StudentActivity::with('student.section')->latest('created_at')->latest('id');

            if ($q !== '') {
                $query->where(function ($w) use ($q, $like) {
                    $like($w, 'student_activities.summary', $q);
                    $w->orWhereHas('student', function ($s) use ($q, $like) {
                        $s->where(function ($x) use ($q, $like) {
                            $like($x, 'students.full_name', $q);
                            $like($x, 'students.student_number', $q, true);
                        });
                    });
                });
            }
            if ($type && in_array($type, StudentActivity::TYPES, true)) $query->where('type', $type);
            if ($sectionId !== null && $sectionId !== '') $query->where('section_id', (int) $sectionId);
            if ($unreadOnly) $query->whereNull('read_at');
            if ($from) $query->whereDate('created_at', '>=', $from);
            if ($to) $query->whereDate('created_at', '<=', $to);

            $sections = Section::orderBy('name')->get(['id', 'name']);

            $rows = $query->paginate(25)->withQueryString()->through(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'summary' => $a->summary,
                'student_name' => $a->student?->full_name,
                'student_number' => $a->student?->student_number,
                'section' => $a->student?->section?->name,
                'read' => (bool) $a->read_at,
                'wall_post_id' => $a->type === 'wall_post' ? ($a->meta['post_id'] ?? null) : null,
                'wall_hidden' => ($a->type === 'wall_post' && isset($a->meta['post_id']))
                    ? (($w = \Illuminate\Support\Facades\DB::table('freedom_wall_posts')->where('id', $a->meta['post_id'])->first(['hidden_at'])) ? (bool) $w->hidden_at : null)
                    : null,
                'at' => $a->created_at?->toIso8601String(),
            ]);
        }

        return Inertia::render('Admin/Logs/Index', [
            'tab' => $tab,
            'filters' => [
                'q' => $q,
                'from' => $from,
                'to' => $to,
                'result' => $request->query('result'),
                'type' => $type,
                'section_id' => $sectionId,
                'unread' => $unreadOnly,
            ],
            'rows' => $rows,
            'sections' => $sections,
        ]);
    }

    public function markRead(Request $request)
    {
        $query = StudentActivity::whereNull('read_at');

        if (is_array($request->input('ids'))) {
            $query->whereIn('id', $request->input('ids'));
        }

        $query->update(['read_at' => now()]);

        return back();
    }

    public function toggleWallPost(int $id)
    {
        $post = \Illuminate\Support\Facades\DB::table('freedom_wall_posts')->where('id', $id)->first(['id', 'hidden_at']);
        abort_if(! $post, 404);

        \Illuminate\Support\Facades\DB::table('freedom_wall_posts')
            ->where('id', $id)
            ->update(['hidden_at' => $post->hidden_at ? null : now()]);

        return back();
    }
}
