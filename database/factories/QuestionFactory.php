<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::SingleChoice,
            'question_text' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'points' => 1,
            'sort_order' => 0,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(fn () => ['type' => QuestionType::MultipleChoice]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn () => ['type' => QuestionType::TrueFalse]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn () => ['type' => QuestionType::ShortAnswer]);
    }

    public function worth(int $points): static
    {
        return $this->state(fn () => ['points' => $points]);
    }
}
