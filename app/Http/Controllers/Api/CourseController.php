<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Courses
 *
 * APIs for browsing published courses.
 */
class CourseController extends Controller
{
    public function __construct(
        private CourseService $courseService
    ) {}

    /**
     * List Courses
     *
     * Get paginated list of published courses with optional filters.
     *
     * @unauthenticated
     *
     * @queryParam category integer Filter by category ID. Example: 1
     * @queryParam level string Filter by level (beginner, intermediate, advanced). Example: beginner
     * @queryParam search string Search by title or description. Example: laravel
     * @queryParam is_free boolean Filter free courses only. Example: true
     * @queryParam sort string Sort order (latest, price_asc, price_desc, title). Example: latest
     * @queryParam per_page integer Results per page (default: 15). Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->list($request->only([
            'category', 'level', 'search', 'is_free', 'sort',
        ]))->paginate($request->integer('per_page', 15));

        return CourseResource::collection($courses);
    }

    /**
     * Show Course
     *
     * Get detailed information about a published course including modules and lessons.
     *
     * @unauthenticated
     */
    public function show(Course $course): CourseDetailResource
    {
        $course->load(['instructor', 'category', 'modules.lessons', 'media']);

        return new CourseDetailResource($course);
    }
}
