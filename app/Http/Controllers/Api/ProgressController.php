<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseProgressResource;
use App\Http\Resources\LessonProgressResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Progress
 *
 * APIs for tracking student progress through courses and lessons.
 */
class ProgressController extends Controller
{
    public function __construct(
        private ProgressService $progressService
    ) {}

    /**
     * Course Progress
     *
     * Get the authenticated student's progress for a course.
     *
     * @authenticated
     */
    public function courseProgress(Request $request, Course $course): CourseProgressResource|JsonResponse
    {
        $enrollment = $this->getActiveEnrollment($request, $course);

        if (! $enrollment) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $progress = $this->progressService->getCourseProgress($request->user(), $enrollment);

        return (new CourseProgressResource($progress))->response()->setStatusCode(200);
    }

    /**
     * Lesson Progress List
     *
     * Get all lesson progress records for an enrolled course.
     *
     * @authenticated
     */
    public function lessonProgressIndex(Request $request, Course $course)
    {
        $enrollment = $this->getActiveEnrollment($request, $course);

        if (! $enrollment) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $progress = $this->progressService->getLessonProgress($request->user(), $enrollment);

        return LessonProgressResource::collection($progress);
    }

    /**
     * Complete Lesson
     *
     * Mark a lesson as completed for the authenticated student.
     *
     * @authenticated
     */
    public function completeLesson(Request $request, Lesson $lesson): LessonProgressResource|JsonResponse
    {
        $course = $lesson->module->course;
        $enrollment = $this->getActiveEnrollment($request, $course);

        if (! $enrollment) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $progress = $this->progressService->markLessonComplete(
            $request->user(),
            $lesson,
            $enrollment
        );

        return new LessonProgressResource($progress);
    }

    /**
     * Update Watch Time
     *
     * Record watch time for a video lesson.
     *
     * @authenticated
     *
     * @bodyParam seconds int required The number of seconds watched. Example: 120
     */
    public function updateWatchTime(Request $request, Lesson $lesson): LessonProgressResource|JsonResponse
    {
        $validated = $request->validate([
            'seconds' => ['required', 'integer', 'min:1', 'max:86400'],
        ]);

        $course = $lesson->module->course;
        $enrollment = $this->getActiveEnrollment($request, $course);

        if (! $enrollment) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $progress = $this->progressService->updateWatchTime(
            $request->user(),
            $lesson,
            $enrollment,
            $validated['seconds']
        );

        return new LessonProgressResource($progress);
    }

    private function getActiveEnrollment(Request $request, Course $course): ?Enrollment
    {
        return Enrollment::where('student_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [
                \App\Enums\EnrollmentStatus::Active,
                \App\Enums\EnrollmentStatus::Completed,
            ])
            ->first();
    }
}
