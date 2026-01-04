<?php

namespace Tests\Feature\Api\Progress;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Course $course;

    private Module $module;

    private Lesson $lesson;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('instructor');

        $this->course = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $this->module = Module::factory()->create([
            'course_id' => $this->course->id,
        ]);

        $this->lesson = Lesson::factory()->create([
            'module_id' => $this->module->id,
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);
    }

    public function test_student_can_complete_lesson(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/complete');

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.lesson_id', $this->lesson->id);

        $this->assertDatabaseHas('lesson_progress', [
            'student_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
        ]);
    }

    public function test_completing_same_lesson_twice_is_idempotent(): void
    {
        Sanctum::actingAs($this->student);

        $this->postJson('/api/lessons/'.$this->lesson->id.'/complete');
        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/complete');

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseCount('lesson_progress', 1);
    }

    public function test_unenrolled_student_cannot_complete_lesson(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        Sanctum::actingAs($otherStudent);

        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/complete');

        $response->assertStatus(403);
    }

    public function test_student_can_update_watch_time(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/watch-time', [
            'seconds' => 120,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.watch_time_seconds', 120)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_watch_time_accumulates(): void
    {
        Sanctum::actingAs($this->student);

        $this->postJson('/api/lessons/'.$this->lesson->id.'/watch-time', ['seconds' => 60]);
        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/watch-time', ['seconds' => 90]);

        $response->assertOk()
            ->assertJsonPath('data.watch_time_seconds', 150);
    }

    public function test_watch_time_requires_valid_seconds(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/watch-time', [
            'seconds' => 0,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['seconds']);
    }

    public function test_unauthenticated_cannot_complete_lesson(): void
    {
        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/complete');

        $response->assertUnauthorized();
    }
}
