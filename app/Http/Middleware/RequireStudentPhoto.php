<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStudentPhoto
{
    public function handle(Request $request, Closure $next): Response
    {
        $number = $request->input('student_number');
        $password = $request->input('password');

        if (is_string($number) && is_string($password)) {
            $student = Student::where('student_number', $number)->first();

            if ($student && $student->password === $password && empty($student->photo_path)) {
                return response()->json([
                    'needs_photo' => true,
                    'message' => 'Please upload your photo (max 40KB) before signing in.',
                ], 403);
            }
        }

        return $next($request);
    }
}
