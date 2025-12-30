<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Instructor > Quizzes
 *
 * APIs for instructors to manage quizzes for their courses.
 *
 * @authenticated
 */
class QuizController extends Controller
{
    /**
     * List Course Quizzes
     *
     * Get all quizzes for an instructor's course.
     */
    public function index(Request $request, Course $course): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('update', $course);

        $quizzes = $course->quizzes()
            ->withCount('questions')
            ->latest()
            ->get();

        return QuizResource::collection($quizzes);
    }

    /**
     * Create Quiz
     *
     * Create a new quiz for a course with questions and answers.
     *
     * @response 201
     */
    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'pass_percentage' => ['integer', 'min:0', 'max:100'],
            'max_attempts' => ['integer', 'min:0'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'string', 'in:single_choice,multiple_choice,true_false,short_answer'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.points' => ['integer', 'min:1'],
            'questions.*.answers' => ['required', 'array', 'min:1'],
            'questions.*.answers.*.answer_text' => ['required', 'string'],
            'questions.*.answers.*.is_correct' => ['required', 'boolean'],
        ]);

        $quiz = $course->quizzes()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'pass_percentage' => $validated['pass_percentage'] ?? 70,
            'max_attempts' => $validated['max_attempts'] ?? 3,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $question = $quiz->questions()->create([
                'type' => $questionData['type'],
                'question_text' => $questionData['question_text'],
                'explanation' => $questionData['explanation'] ?? null,
                'points' => $questionData['points'] ?? 1,
                'sort_order' => $index,
            ]);

            foreach ($questionData['answers'] as $answerIndex => $answerData) {
                $question->answers()->create([
                    'answer_text' => $answerData['answer_text'],
                    'is_correct' => $answerData['is_correct'],
                    'sort_order' => $answerIndex,
                ]);
            }
        }

        $quiz->load(['questions.answers']);
        $quiz->loadCount('questions');

        return (new QuizResource($quiz))->response()->setStatusCode(201);
    }

    /**
     * Show Quiz
     *
     * Get quiz details with questions and answers (including correct flags).
     */
    public function show(Request $request, Course $course, Quiz $quiz): QuizResource|JsonResponse
    {
        $this->authorize('update', $course);

        if ($quiz->course_id !== $course->id) {
            return response()->json(['message' => 'Quiz not found for this course.'], 404);
        }

        $quiz->load(['questions.answers']);
        $quiz->loadCount('questions');

        return new QuizResource($quiz);
    }

    /**
     * Publish Quiz
     *
     * Toggle the published status of a quiz.
     */
    public function publish(Request $request, Course $course, Quiz $quiz): QuizResource|JsonResponse
    {
        $this->authorize('update', $course);

        if ($quiz->course_id !== $course->id) {
            return response()->json(['message' => 'Quiz not found for this course.'], 404);
        }

        $quiz->update(['is_published' => ! $quiz->is_published]);

        return new QuizResource($quiz->fresh());
    }

    /**
     * Delete Quiz
     *
     * Delete a quiz and all its questions/answers.
     */
    public function destroy(Request $request, Course $course, Quiz $quiz): JsonResponse
    {
        $this->authorize('update', $course);

        if ($quiz->course_id !== $course->id) {
            return response()->json(['message' => 'Quiz not found for this course.'], 404);
        }

        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted successfully.']);
    }
}
