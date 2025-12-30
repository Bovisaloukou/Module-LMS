<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Instructor > Courses
 *
 * APIs for instructors to manage their courses.
 *
 * @authenticated
 */
class CourseController extends Controller
{
    public function __construct(
        private CourseService $courseService
    ) {}

    /**
     * List My Courses
     *
     * Get all courses owned by the authenticated instructor.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = Course::query()
            ->byInstructor($request->user()->id)
            ->with(['category', 'media'])
            ->withCount(['modules', 'enrollments'])
            ->latest()
            ->paginate(15);

        return CourseResource::collection($courses);
    }

    /**
     * Create Course
     *
     * Create a new course as draft.
     *
     * @response 201
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create(
            $request->user(),
            $request->validated()
        );

        return (new CourseDetailResource($course->load(['instructor', 'category'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show Course
     *
     * Get detailed information about an instructor's course.
     */
    public function show(Request $request, Course $course): CourseDetailResource
    {
        $this->authorize('update', $course);

        $course->load(['instructor', 'category', 'modules.lessons', 'media']);

        return new CourseDetailResource($course);
    }

    /**
     * Update Course
     *
     * Update a course's information.
     */
    public function update(UpdateCourseRequest $request, Course $course): CourseDetailResource
    {
        $this->authorize('update', $course);

        $course = $this->courseService->update($course, $request->validated());
        $course->load(['instructor', 'category', 'modules.lessons', 'media']);

        return new CourseDetailResource($course);
    }

    /**
     * Delete Course
     *
     * Soft delete a course.
     */
    public function destroy(Request $request, Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $this->courseService->delete($course);

        return response()->json(['message' => 'Course deleted successfully.']);
    }

    /**
     * Publish Course
     *
     * Change course status to published.
     */
    public function publish(Request $request, Course $course): CourseDetailResource
    {
        $this->authorize('publish', $course);

        $course = $this->courseService->publish($course);
        $course->load(['instructor', 'category', 'modules.lessons', 'media']);

        return new CourseDetailResource($course);
    }
}
