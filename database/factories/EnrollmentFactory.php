<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'course_id' => Course::factory(),
            'price_paid' => fake()->randomFloat(2, 0, 199.99),
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => ['price_paid' => 0]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => EnrollmentStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => [
            'status' => EnrollmentStatus::Refunded,
        ]);
    }
}
