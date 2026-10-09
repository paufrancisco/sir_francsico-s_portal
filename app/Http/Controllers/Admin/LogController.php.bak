<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassmateMessage;
use App\Models\StudentLoginLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'messages' ? 'messages' : 'logins';
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        if ($tab === 'logins') {
            $query = StudentLoginLog::with('student.section')->latest('created_at')->latest('id');

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('student_number', 'like', "%{$q}%")
                      ->orWhereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$q}%"));
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
        } else {
            $query = ClassmateMessage::with('sender.section')->latest('created_at')->latest('id');

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('body', 'like', "%{$q}%")
                      ->orWhereHas('sender', fn ($s) => $s->where('full_name', 'like', "%{$q}%")
                          ->orWhere('student_number', 'like', "%{$q}%"));
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
        }

        return Inertia::render('Admin/Logs/Index', [
            'tab' => $tab,
            'filters' => [
                'q' => $q,
                'from' => $from,
                'to' => $to,
                'result' => $request->query('result'),
            ],
            'rows' => $rows,
        ]);
    }
}
