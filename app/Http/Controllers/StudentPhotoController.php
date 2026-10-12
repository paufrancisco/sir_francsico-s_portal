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

    public function change(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        $key = 'photo-change:' . $student->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $mins = (int) ceil(\Illuminate\Support\Facades\RateLimiter::availableIn($key) / 60);
            return response()->json([
                'message' => "You've changed your photo too many times. Try again in {$mins} minute(s).",
            ], 429);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:40',
        ], [
            'photo.max' => 'Photo must be 40KB or smaller.',
            'photo.image' => 'The file must be an image.',
            'photo.mimes' => 'Photo must be a JPG or PNG.',
        ]);

        $oldPath = $student->photo_path;
        $newPath = $request->file('photo')->store('students', 'supabase');
        $student->update(['photo_path' => $newPath]);

        // burahin lang ang luma pagkatapos ma-save ang bago
        if ($oldPath && $oldPath !== $newPath) {
            \Illuminate\Support\Facades\Storage::disk('supabase')->delete($oldPath);
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);

        \Illuminate\Support\Facades\Log::info('Student changed photo', [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'at' => now()->toDateTimeString(),
        ]);

        return response()->json(['photo_url' => $student->fresh()->photo_url]);
    }
}
