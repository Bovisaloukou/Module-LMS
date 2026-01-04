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

class CourseProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Course $course;

    private Module $module;

    private array $lessons;

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

        $this->lessons = [
            Lesson::factory()->create(['module_id' => $this->module->id, 'sort_order' => 1]),
            Lesson::factory()->create(['module_id' => $this->module->id, 'sort_order' => 2]),
            Lesson::factory()->create(['module_id' => $this->module->id, 'sort_order' => 3]),
        ];

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);
    }

    public function test_student_can_view_course_progress(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress');

        $response->assertOk()
            ->assertJsonPath('data.course_id', $this->course->id)
            ->assertJsonPath('data.total_lessons', 3)
            ->assertJsonPath('data.completed_lessons', 0)
            ->assertJsonPath('data.percentage', 0)
            ->assertJsonPath('data.status', 'not_started');
    }

    public function test_progress_updates_after_completing_lesson(): void
    {
        Sanctum::actingAs($this->student);

        $this->postJson('/api/lessons/'.$this->lessons[0]->id.'/complete');

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress');

        $response->assertOk()
            ->assertJsonPath('data.completed_lessons', 1)
            ->assertJsonPath('data.percentage', 33.33)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_course_completes_when_all_lessons_done(): void
    {
        Sanctum::actingAs($this->student);

        foreach ($this->lessons as $lesson) {
            $this->postJson('/api/lessons/'.$lesson->id.'/complete');
        }

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress');

        $response->assertOk()
            ->assertJsonPath('data.completed_lessons', 3)
            ->assertJsonPath('data.percentage', 100)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'status' => 'completed',
        ]);
    }

    public function test_student_can_view_lesson_progress_list(): void
    {
        Sanctum::actingAs($this->student);

        $this->postJson('/api/lessons/'.$this->lessons[0]->id.'/complete');
        $this->postJson('/api/lessons/'.$this->lessons[1]->id.'/watch-time', ['seconds' => 60]);

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress/lessons');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_unenrolled_student_cannot_view_progress(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        Sanctum::actingAs($otherStudent);

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress');

        $response->assertStatus(403);
    }

    public function test_watch_time_updates_last_accessed(): void
    {
        Sanctum::actingAs($this->student);

        $this->postJson('/api/lessons/'.$this->lessons[0]->id.'/watch-time', ['seconds' => 30]);

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress');

        $response->assertOk()
            ->assertJsonPath('data.last_lesson_id', $this->lessons[0]->id)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertNotNull($response->json('data.last_accessed_at'));
    }

    public function test_completed_enrollment_still_allows_progress_view(): void
    {
        Sanctum::actingAs($this->student);

        foreach ($this->lessons as $lesson) {
            $this->postJson('/api/lessons/'.$lesson->id.'/complete');
        }

        $response = $this->getJson('/api/courses/'.$this->course->id.'/progress');

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }
}
