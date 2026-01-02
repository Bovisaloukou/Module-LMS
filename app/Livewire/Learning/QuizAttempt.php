<?php

namespace App\Livewire\Learning;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt as QuizAttemptModel;
use App\Services\QuizService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class QuizAttempt extends Component
{
    public Quiz $quiz;

    public ?QuizAttemptModel $attempt = null;

    public array $answers = [];

    public bool $showResults = false;

    public ?QuizAttemptModel $completedAttempt = null;

    public function mount(int $quizId): void
    {
        $this->quiz = Quiz::with(['course', 'questions.answers'])
            ->findOrFail($quizId);

        // Verify enrollment
        Enrollment::where('student_id', auth()->id())
            ->where('course_id', $this->quiz->course_id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->firstOrFail();

        // Initialize empty answers
        foreach ($this->quiz->questions as $question) {
            $this->answers[$question->id] = [
                'question_id' => $question->id,
                'answer_id' => null,
                'answer_ids' => [],
                'text_answer' => '',
            ];
        }
    }

    public function startQuiz(QuizService $quizService): void
    {
        try {
            $this->attempt = $quizService->startAttempt(auth()->user(), $this->quiz);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function submitQuiz(QuizService $quizService): void
    {
        if (! $this->attempt) {
            return;
        }

        try {
            $this->completedAttempt = $quizService->submitAttempt(
                $this->attempt,
                array_values($this->answers)
            );
            $this->showResults = true;
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $previousAttempts = QuizAttemptModel::where('quiz_id', $this->quiz->id)
            ->where('student_id', auth()->id())
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get();

        return view('livewire.learning.quiz-attempt', [
            'previousAttempts' => $previousAttempts,
        ])->title('Quiz: '.$this->quiz->title);
    }
}
