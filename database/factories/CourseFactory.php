<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'instructor_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'subtitle' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(10),
            'price' => fake()->randomFloat(2, 0, 199.99),
            'is_free' => false,
            'level' => fake()->randomElement(CourseLevel::cases()),
            'language' => 'en',
            'duration_minutes' => fake()->numberBetween(30, 600),
            'requirements' => ['Basic computer skills', 'Internet connection'],
            'what_you_learn' => ['Core concepts', 'Practical skills', 'Best practices'],
            'status' => CourseStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'is_free' => true,
            'price' => 0,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Archived,
        ]);
    }
}
