<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuizService
{
    public function startAttempt(User $student, Quiz $quiz): QuizAttempt
    {
        $this->validateCanAttempt($student, $quiz);

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
        ]);
    }

    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        if ($attempt->completed_at !== null) {
            throw new \RuntimeException('This attempt has already been submitted.');
        }

        return DB::transaction(function () use ($attempt, $answers) {
            $quiz = $attempt->quiz;
            $questions = $quiz->questions()->with('answers')->get();
            $totalPoints = 0;
            $earnedPoints = 0;

            foreach ($questions as $question) {
                $totalPoints += $question->points;

                $submitted = collect($answers)->firstWhere('question_id', $question->id);

                if (! $submitted) {
                    QuizAttemptAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'is_correct' => false,
                        'points_earned' => 0,
                    ]);

                    continue;
                }

                $result = $this->gradeAnswer($question, $submitted);

                QuizAttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'answer_id' => $result['answer_id'],
                    'text_answer' => $result['text_answer'],
                    'is_correct' => $result['is_correct'],
                    'points_earned' => $result['points_earned'],
                ]);

                if ($result['is_correct']) {
                    $earnedPoints += $result['points_earned'];
                }
            }

            $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
            $passed = $score >= $quiz->pass_percentage;

            $attempt->update([
                'total_points' => $totalPoints,
                'earned_points' => $earnedPoints,
                'score' => $score,
                'passed' => $passed,
                'completed_at' => now(),
            ]);

            return $attempt->fresh()->load('attemptAnswers');
        });
    }

    public function getAttemptCount(User $student, Quiz $quiz): int
    {
        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->count();
    }

    public function canAttempt(User $student, Quiz $quiz): bool
    {
        if ($quiz->max_attempts === 0) {
            return true;
        }

        return $this->getAttemptCount($student, $quiz) < $quiz->max_attempts;
    }

    public function getResults(QuizAttempt $attempt): QuizAttempt
    {
        return $attempt->load(['attemptAnswers.question', 'attemptAnswers.answer', 'quiz']);
    }

    public function getStudentAttempts(User $student, Quiz $quiz)
    {
        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get();
    }

    private function validateCanAttempt(User $student, Quiz $quiz): void
    {
        if (! $quiz->is_published) {
            throw new \RuntimeException('This quiz is not available.');
        }

        $pendingAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->whereNull('completed_at')
            ->first();

        if ($pendingAttempt) {
            throw new \RuntimeException('You have an incomplete attempt. Please complete it first.');
        }

        if (! $this->canAttempt($student, $quiz)) {
            throw new \RuntimeException('Maximum attempts reached for this quiz.');
        }
    }

    private function gradeAnswer(Question $question, array $submitted): array
    {
        return match ($question->type) {
            QuestionType::SingleChoice, QuestionType::TrueFalse => $this->gradeSingleChoice($question, $submitted),
            QuestionType::MultipleChoice => $this->gradeMultipleChoice($question, $submitted),
            QuestionType::ShortAnswer => $this->gradeShortAnswer($question, $submitted),
        };
    }

    private function gradeSingleChoice(Question $question, array $submitted): array
    {
        $answerId = $submitted['answer_id'] ?? null;
        $answer = $answerId ? Answer::find($answerId) : null;

        $isCorrect = $answer && $answer->is_correct && $answer->question_id === $question->id;

        return [
            'answer_id' => $answerId,
            'text_answer' => null,
            'is_correct' => $isCorrect,
            'points_earned' => $isCorrect ? $question->points : 0,
        ];
    }

    private function gradeMultipleChoice(Question $question, array $submitted): array
    {
        $submittedIds = $submitted['answer_ids'] ?? [];
        $correctIds = $question->answers->where('is_correct', true)->pluck('id')->toArray();

        sort($submittedIds);
        sort($correctIds);

        $isCorrect = $submittedIds === $correctIds;

        return [
            'answer_id' => $submittedIds[0] ?? null,
            'text_answer' => json_encode($submittedIds),
            'is_correct' => $isCorrect,
            'points_earned' => $isCorrect ? $question->points : 0,
        ];
    }

    private function gradeShortAnswer(Question $question, array $submitted): array
    {
        $textAnswer = trim($submitted['text_answer'] ?? '');
        $correctAnswers = $question->answers->where('is_correct', true);

        $isCorrect = $correctAnswers->contains(function ($answer) use ($textAnswer) {
            return mb_strtolower($answer->answer_text) === mb_strtolower($textAnswer);
        });

        return [
            'answer_id' => null,
            'text_answer' => $textAnswer,
            'is_correct' => $isCorrect,
            'points_earned' => $isCorrect ? $question->points : 0,
        ];
    }
}
