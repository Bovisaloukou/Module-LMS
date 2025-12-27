<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizResource;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Quizzes
 *
 * APIs for students to take quizzes and view results.
 */
class QuizController extends Controller
{
    public function __construct(
        private QuizService $quizService
    ) {}

    /**
     * Show Quiz
     *
     * Get quiz details with questions and answer options (without correct answers).
     *
     * @authenticated
     */
    public function show(Request $request, Quiz $quiz): QuizResource|JsonResponse
    {
        if (! $this->isEnrolledInCourse($request, $quiz->course_id)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $quiz->load(['questions.answers']);
        $quiz->loadCount('questions');

        return new QuizResource($quiz);
    }

    /**
     * Start Quiz Attempt
     *
     * Begin a new quiz attempt. Returns the attempt ID to use when submitting answers.
     *
     * @authenticated
     */
    public function start(Request $request, Quiz $quiz): QuizAttemptResource|JsonResponse
    {
        if (! $this->isEnrolledInCourse($request, $quiz->course_id)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        try {
            $attempt = $this->quizService->startAttempt($request->user(), $quiz);

            return (new QuizAttemptResource($attempt))->response()->setStatusCode(200);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Submit Quiz Attempt
     *
     * Submit answers for a quiz attempt. Auto-graded and scored.
     *
     * @authenticated
     *
     * @bodyParam answers array required Array of answer objects.
     * @bodyParam answers[].question_id int required The question ID.
     * @bodyParam answers[].answer_id int The selected answer ID (for single choice/true-false).
     * @bodyParam answers[].answer_ids int[] The selected answer IDs (for multiple choice).
     * @bodyParam answers[].text_answer string The text answer (for short answer).
     */
    public function submit(Request $request, Quiz $quiz, int $attemptId): QuizAttemptResource|JsonResponse
    {
        $attempt = $quiz->attempts()
            ->where('id', $attemptId)
            ->where('student_id', $request->user()->id)
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'Attempt not found.'], 404);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer_id' => ['nullable', 'integer'],
            'answers.*.answer_ids' => ['nullable', 'array'],
            'answers.*.answer_ids.*' => ['integer'],
            'answers.*.text_answer' => ['nullable', 'string'],
        ]);

        try {
            $attempt = $this->quizService->submitAttempt($attempt, $validated['answers']);

            return new QuizAttemptResource($attempt);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Quiz Attempt Results
     *
     * Get detailed results for a completed quiz attempt.
     *
     * @authenticated
     */
    public function results(Request $request, Quiz $quiz, int $attemptId): QuizAttemptResource|JsonResponse
    {
        $attempt = $quiz->attempts()
            ->where('id', $attemptId)
            ->where('student_id', $request->user()->id)
            ->whereNotNull('completed_at')
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'Completed attempt not found.'], 404);
        }

        $attempt = $this->quizService->getResults($attempt);

        return new QuizAttemptResource($attempt);
    }

    /**
     * My Attempts
     *
     * List all completed attempts for a quiz.
     *
     * @authenticated
     */
    public function attempts(Request $request, Quiz $quiz): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
    {
        if (! $this->isEnrolledInCourse($request, $quiz->course_id)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $attempts = $this->quizService->getStudentAttempts($request->user(), $quiz);

        return QuizAttemptResource::collection($attempts);
    }

    private function isEnrolledInCourse(Request $request, int $courseId): bool
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return true;
        }

        return Enrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();
    }
}
