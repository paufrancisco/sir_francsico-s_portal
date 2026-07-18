<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentAuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Student/Login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('student')->attempt($validated, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('student.dashboard'));
        }

        return back()->withErrors([
            'student_number' => 'Mali yung student number o password.',
        ])->onlyInput('student_number');
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }
}