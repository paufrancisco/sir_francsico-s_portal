<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Students/Index', [
            'students' => $this->studentList(),
            'revealed' => false,
        ]);
    }

    public function reveal(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Maling password.']);
        }

        return Inertia::render('Admin/Students/Index', [
            'students' => $this->studentList(withPassword: true),
            'revealed' => true,
        ]);
    }

    private function studentList(bool $withPassword = false)
    {
        return Student::with('section')->orderBy('full_name')->get()->map(fn ($s) => [
            'id' => $s->id,
            'student_number' => $s->student_number,
            'full_name' => $s->full_name,
            'section' => $s->section?->name ?? '—',
            'password' => $withPassword ? $s->password : null,
        ]);
    }
}