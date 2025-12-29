<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Reviews
 *
 * APIs for course reviews.
 */
class ReviewController extends Controller
{
    /**
     * Course Reviews
     *
     * List all approved reviews for a course.
     */
    public function index(Course $course): AnonymousResourceCollection
    {
        $reviews = $course->reviews()
            ->where('is_approved', true)
            ->with('student:id,name')
            ->latest()
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    /**
     * Create Review
     *
     * Submit a review for an enrolled course. One review per student per course.
     *
     * @authenticated
     *
     * @bodyParam rating int required Rating from 1 to 5. Example: 4
     * @bodyParam comment string Optional review comment. Example: Great course!
     */
    public function store(Request $request, Course $course): ReviewResource|JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = $request->user();

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'You must be enrolled in this course to review it.'], 403);
        }

        $existing = Review::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already reviewed this course.'], 409);
        }

        $review = Review::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_approved' => true,
        ]);

        $review->load('student:id,name');

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }

    /**
     * Update Review
     *
     * Update your own review.
     *
     * @authenticated
     */
    public function update(Request $request, Course $course, Review $review): ReviewResource|JsonResponse
    {
        if ($review->student_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($review->course_id !== $course->id) {
            return response()->json(['message' => 'Review not found for this course.'], 404);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($validated);
        $review->load('student:id,name');

        return new ReviewResource($review);
    }

    /**
     * Delete Review
     *
     * Delete your own review.
     *
     * @authenticated
     */
    public function destroy(Request $request, Course $course, Review $review): JsonResponse
    {
        $user = $request->user();

        if ($review->student_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($review->course_id !== $course->id) {
            return response()->json(['message' => 'Review not found for this course.'], 404);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }
}
