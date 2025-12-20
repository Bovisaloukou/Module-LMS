<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'module_id' => Module::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => LessonType::Video,
            'content' => fake()->paragraphs(2, true),
            'video_url' => null,
            'duration_minutes' => fake()->numberBetween(5, 45),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_free_preview' => false,
            'is_published' => true,
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => ['type' => LessonType::Video]);
    }

    public function text(): static
    {
        return $this->state(fn () => [
            'type' => LessonType::Text,
            'duration_minutes' => fake()->numberBetween(5, 15),
        ]);
    }

    public function freePreview(): static
    {
        return $this->state(fn () => ['is_free_preview' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
