<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Models\StudentLoginLog;
use Closure;
use Illuminate\Http\Request;

class LogStudentPortalAccess
{
    public function handle(Request $request, Closure $next, string $source = 'portal')
    {
        $response = $next($request);

        try {
            $number = trim((string) $request->input('student_number'));
            $status = $response->getStatusCode();

            // Skip empty input and "AI busy" (429): credentials were not the issue.
            if ($number !== '' && $status !== 429) {
                $ok = $status < 400;
                $student = Student::where('student_number', $number)->first();

                $duplicate = $ok && $student && StudentLoginLog::where('student_id', $student->id)
                    ->where('source', $source)
                    ->where('success', true)
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->exists();

                if (! $duplicate) {
                    StudentLoginLog::create([
                        'student_id' => $student?->id,
                        'student_number' => mb_substr($number, 0, 64),
                        'source' => $source,
                        'success' => $ok,
                        'ip' => $request->ip(),
                        'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            report($e); // logging must never break the student's request
        }

        return $response;
    }
}
