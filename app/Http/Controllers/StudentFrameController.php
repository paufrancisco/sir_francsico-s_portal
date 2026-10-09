<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentFrameController extends Controller
{
    public const FRAMES = ['gold', 'aurora', 'neon', 'ice', 'rose', 'royal', 'flame', 'rainbow'];

    // POST, verified via student_number + password in the body (same as classmate messages).
    // No "frame" in the body = sign-in check only (returns the current frame).
    // With "frame" = saves it. 'gold' is the default look, stored as NULL.
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
            'frame' => ['nullable', 'string', Rule::in(self::FRAMES)],
        ]);

        $student = $this->verify($data['student_number'], $data['password']);

        if ($request->filled('frame')) {
            $student->update(['frame' => $data['frame'] === 'gold' ? null : $data['frame']]);
        }

        return response()->json([
            'message' => $request->filled('frame') ? 'Frame saved.' : 'Signed in.',
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'photo_url' => $student->photo_url,
                'frame' => $student->frame ?? 'gold',
            ],
        ]);
    }

    private function verify(string $studentNumber, string $password): Student
    {
        $student = Student::where('student_number', $studentNumber)->first();

        if (! $student || ! hash_equals((string) $student->password, $password)) {
            throw ValidationException::withMessages([
                'student_number' => 'Invalid student number or password.',
            ]);
        }

        if (is_null($student->password_changed_at)) {
            throw ValidationException::withMessages([
                'student_number' => 'Change your password first.',
            ]);
        }

        return $student;
    }
}
