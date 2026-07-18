<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminChatController extends Controller
{
    public function index()
    {
        $studentIds = Message::select('student_id')->distinct()->pluck('student_id');

        $students = Student::whereIn('id', $studentIds)
            ->get(['id', 'full_name', 'student_number'])
            ->map(function ($s) {
                $last = Message::where('student_id', $s->id)->latest()->first();
                return [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'student_number' => $s->student_number,
                    'last_message' => $last?->body,
                    'last_at' => $last?->created_at,
                    'needs_review' => (bool) $last?->needs_review,
                ];
            })
            ->sortByDesc('last_at')
            ->values();

        return Inertia::render('Admin/Chat/Index', ['students' => $students]);
    }

    public function show(Student $student)
    {
        return Inertia::render('Admin/Chat/Show', [
            'student' => $student->only('id', 'full_name', 'student_number'),
            'messages' => Message::where('student_id', $student->id)
                ->orderBy('created_at')
                ->get(['id', 'sender', 'body', 'needs_review', 'created_at']),
        ]);
    }

    public function reply(Request $request, Student $student)
    {
        $request->validate(['body' => 'required|string|max:1000']);

        Message::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'sender' => 'admin',
            'body' => $request->body,
        ]);

        return back();
    }
}