<?php

namespace Database\Factories;

use App\Models\Discussion;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discussion>
 */
class DiscussionFactory extends Factory
{
    protected $model = Discussion::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'is_resolved' => false,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['is_resolved' => true]);
    }
}
