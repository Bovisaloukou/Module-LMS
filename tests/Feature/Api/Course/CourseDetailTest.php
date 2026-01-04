<?php

namespace Tests\Feature\Api\Course;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_can_view_published_course_details(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $course = Course::factory()->published()->create(['instructor_id' => $instructor->id]);

        $response = $this->getJson('/api/courses/'.$course->slug);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'slug', 'subtitle', 'description',
                    'short_description', 'price', 'is_free', 'level',
                    'language', 'duration_minutes', 'requirements',
                    'what_you_learn', 'status', 'instructor', 'category',
                    'modules', 'average_rating', 'student_count',
                    'lessons_count', 'published_at', 'created_at',
                ],
            ]);
    }

    public function test_course_detail_includes_modules_and_lessons(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $course = Course::factory()->published()->create(['instructor_id' => $instructor->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->count(3)->create(['module_id' => $module->id]);

        $response = $this->getJson('/api/courses/'.$course->slug);

        $response->assertOk()
            ->assertJsonCount(1, 'data.modules')
            ->assertJsonCount(3, 'data.modules.0.lessons');
    }

    public function test_course_detail_includes_instructor_info(): void
    {
        $instructor = User::factory()->create(['name' => 'John Instructor']);
        $instructor->assignRole('instructor');

        $course = Course::factory()->published()->create(['instructor_id' => $instructor->id]);

        $response = $this->getJson('/api/courses/'.$course->slug);

        $response->assertOk()
            ->assertJsonPath('data.instructor.name', 'John Instructor');
    }

    public function test_course_detail_shows_category(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $course = Course::factory()->published()->create(['instructor_id' => $instructor->id]);

        $response = $this->getJson('/api/courses/'.$course->slug);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['category' => ['id', 'name', 'slug']]]);
    }

    public function test_returns_404_for_nonexistent_course(): void
    {
        $response = $this->getJson('/api/courses/nonexistent-slug');

        $response->assertNotFound();
    }
}
