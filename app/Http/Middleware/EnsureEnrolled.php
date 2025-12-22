<?php

namespace App\Http\Middleware;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        $course = $request->route('course');

        if (! $course instanceof Course) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->hasRole('admin') || $course->instructor_id === $user->id) {
            return $next($request);
        }

        $isEnrolled = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();

        if (! $isEnrolled) {
            abort(403, 'You must be enrolled in this course.');
        }

        return $next($request);
    }
}
