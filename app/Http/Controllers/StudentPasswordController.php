<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentPasswordController extends Controller
{
    public function change(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->current_password) {
            return response()->json(['message' => 'Mali ang student number o current password.'], 422);
        }

        $student->password = $request->new_password;
        $student->password_changed_at = now();
        $student->save();

        return response()->json(['message' => 'Na-update na ang password mo.']);
    }
}