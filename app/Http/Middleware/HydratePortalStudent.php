<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HydratePortalStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = Auth::guard('student')->user();

        if ($student && ! $request->filled('student_number')) {
            $request->merge([
                'student_number' => $student->student_number,
                'password' => $student->password,
                'current_password' => $student->password,
            ]);
        }

        return $next($request);
    }
}
