<?php

namespace Tests\Feature\Api\Instructor;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('instructor');
    }

    public function test_instructor_can_list_own_courses(): void
    {
        Sanctum::actingAs($this->instructor);

        Course::factory()->count(3)->create(['instructor_id' => $this->instructor->id]);

        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('instructor');
        Course::factory()->count(2)->create(['instructor_id' => $otherInstructor->id]);

        $response = $this->getJson('/api/instructor/courses');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_instructor_can_create_course(): void
    {
        Sanctum::actingAs($this->instructor);

        $category = Category::factory()->create();

        $response = $this->postJson('/api/instructor/courses', [
            'title' => 'My New Course',
            'category_id' => $category->id,
            'description' => 'A great course about things.',
            'price' => 29.99,
            'level' => 'beginner',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'My New Course')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('courses', [
            'title' => 'My New Course',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    public function test_instructor_can_update_own_course(): void
    {
        Sanctum::actingAs($this->instructor);

        $course = Course::factory()->create(['instructor_id' => $this->instructor->id]);

        $response = $this->putJson('/api/instructor/courses/'.$course->id, [
            'title' => 'Updated Title',
            'price' => 39.99,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_instructor_cannot_update_other_instructors_course(): void
    {
        Sanctum::actingAs($this->instructor);

        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('instructor');
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);

        $response = $this->putJson('/api/instructor/courses/'.$course->id, [
            'title' => 'Hacked Title',
        ]);

        $response->assertForbidden();
    }

    public function test_instructor_can_delete_own_course(): void
    {
        Sanctum::actingAs($this->instructor);

        $course = Course::factory()->create(['instructor_id' => $this->instructor->id]);

        $response = $this->deleteJson('/api/instructor/courses/'.$course->id);

        $response->assertOk()
            ->assertJson(['message' => 'Course deleted successfully.']);

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_instructor_can_publish_own_course(): void
    {
        Sanctum::actingAs($this->instructor);

        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => CourseStatus::Draft,
        ]);

        $response = $this->postJson('/api/instructor/courses/'.$course->id.'/publish');

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => 'published',
        ]);
    }

    public function test_student_cannot_access_instructor_routes(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/instructor/courses');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_instructor_routes(): void
    {
        $response = $this->getJson('/api/instructor/courses');

        $response->assertUnauthorized();
    }

    public function test_create_course_requires_title(): void
    {
        Sanctum::actingAs($this->instructor);

        $response = $this->postJson('/api/instructor/courses', [
            'description' => 'A course without a title.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
