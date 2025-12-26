<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Enrollments
 *
 * APIs for course enrollment.
 *
 * @authenticated
 */
class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    /**
     * Enroll in Course
     *
     * Enroll the authenticated student in a course. Free courses are enrolled instantly.
     * Paid courses return a Stripe client_secret for payment.
     *
     * @response 201 {"enrollment": {"id": 1, "student_id": 1, "course_id": 1, "price_paid": "0.00", "status": "active"}, "requires_payment": false}
     * @response 201 {"enrollment": {"id": 1}, "requires_payment": true, "client_secret": "pi_xxx_secret_xxx", "payment_intent_id": "pi_xxx"}
     */
    public function enroll(Request $request, Course $course): JsonResponse
    {
        try {
            $result = $this->enrollmentService->enroll($request->user(), $course);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        $data = [
            'enrollment' => new EnrollmentResource($result['enrollment']),
            'requires_payment' => $result['requires_payment'],
        ];

        if ($result['requires_payment']) {
            $data['client_secret'] = $result['client_secret'];
            $data['payment_intent_id'] = $result['payment_intent_id'];
        }

        return response()->json($data, 201);
    }

    /**
     * List My Enrollments
     *
     * Get all enrollments for the authenticated student.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->with(['course.instructor', 'course.category', 'course.media'])
            ->latest('enrolled_at')
            ->paginate(15);

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Show Enrollment
     *
     * Get details of a specific enrollment.
     */
    public function show(Request $request, Enrollment $enrollment): EnrollmentResource|JsonResponse
    {
        if ($enrollment->student_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $enrollment->load(['course.instructor', 'course.category']);

        return new EnrollmentResource($enrollment);
    }
}
