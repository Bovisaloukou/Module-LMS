<?php

namespace Tests\Feature\Api\Course;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_can_list_published_courses(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->published()->count(3)->create(['instructor_id' => $instructor->id]);
        Course::factory()->create(['instructor_id' => $instructor->id]); // draft, should not appear

        $response = $this->getJson('/api/courses');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_course_list_returns_correct_structure(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->published()->create(['instructor_id' => $instructor->id]);

        $response = $this->getJson('/api/courses');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'title', 'slug', 'subtitle', 'short_description',
                        'price', 'is_free', 'level', 'language', 'duration_minutes',
                        'status', 'instructor', 'category', 'published_at', 'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_filter_courses_by_category(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        Course::factory()->published()->count(2)->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
        ]);
        Course::factory()->published()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $otherCategory->id,
        ]);

        $response = $this->getJson('/api/courses?category='.$category->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_courses_by_level(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->published()->create([
            'instructor_id' => $instructor->id,
            'level' => CourseLevel::Beginner,
        ]);
        Course::factory()->published()->create([
            'instructor_id' => $instructor->id,
            'level' => CourseLevel::Advanced,
        ]);

        $response = $this->getJson('/api/courses?level=beginner');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_free_courses(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->published()->free()->create(['instructor_id' => $instructor->id]);
        Course::factory()->published()->create(['instructor_id' => $instructor->id]);

        $response = $this->getJson('/api/courses?is_free=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_search_courses_by_title(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->published()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Laravel Masterclass',
        ]);
        Course::factory()->published()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Python for Beginners',
        ]);

        $response = $this->getJson('/api/courses?search=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Masterclass');
    }

    public function test_draft_courses_are_not_listed(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => CourseStatus::Draft,
        ]);

        $response = $this->getJson('/api/courses');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_courses_list_is_paginated(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Course::factory()->published()->count(20)->create(['instructor_id' => $instructor->id]);

        $response = $this->getJson('/api/courses?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }
}
