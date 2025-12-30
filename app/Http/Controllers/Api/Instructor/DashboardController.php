<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Instructor > Dashboard
 *
 * APIs for the instructor dashboard overview.
 *
 * @authenticated
 */
class DashboardController extends Controller
{
    /**
     * Dashboard Stats
     *
     * Get aggregated statistics for the authenticated instructor.
     */
    public function stats(Request $request): JsonResponse
    {
        $instructorId = $request->user()->id;

        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        $totalCourses = $courseIds->count();
        $publishedCourses = Course::where('instructor_id', $instructorId)
            ->where('status', CourseStatus::Published)
            ->count();

        $totalStudents = Enrollment::whereIn('course_id', $courseIds)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->distinct('student_id')
            ->count('student_id');

        $totalRevenue = Enrollment::whereIn('course_id', $courseIds)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->sum('price_paid');

        $avgRating = Review::whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->avg('rating');

        return response()->json([
            'data' => [
                'total_courses' => $totalCourses,
                'published_courses' => $publishedCourses,
                'total_students' => $totalStudents,
                'total_revenue' => round((float) $totalRevenue, 2),
                'average_rating' => $avgRating ? round((float) $avgRating, 1) : null,
            ],
        ]);
    }
}
