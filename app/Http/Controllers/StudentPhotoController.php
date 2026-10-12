<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentPhotoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        if ($student->photo_path) {
            return response()->json([
                'message' => 'You already have a photo.',
                'photo_url' => $student->photo_url,
            ], 409);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:40',
        ], [
            'photo.max' => 'Photo must be 40KB or smaller.',
            'photo.image' => 'The file must be an image.',
            'photo.mimes' => 'Photo must be a JPG or PNG.',
        ]);

        $path = $request->file('photo')->store('students', 'supabase');
        $student->update(['photo_path' => $path]);

        return response()->json(['photo_url' => $student->fresh()->photo_url]);
    }
}
