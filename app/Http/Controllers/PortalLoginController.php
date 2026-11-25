<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalLoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::where('student_number', $data['student_number'])->first();

        if (! $student || ! hash_equals((string) $student->password, $data['password'])) {
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        if (is_null($student->password_changed_at)) {
            return response()->json(['must_change_password' => true]);
        }

        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return response()->json([
            'student' => $student->only('id', 'student_number', 'full_name'),
        ]);
    }

    public function destroy(): JsonResponse
    {
        Auth::guard('student')->logout();

        return response()->json(['ok' => true]);
    }
}
