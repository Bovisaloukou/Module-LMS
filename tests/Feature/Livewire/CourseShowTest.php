<?php

namespace Tests\Feature\Livewire;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Livewire\CourseShow;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_course_detail_page_renders(): void
    {
        $course = Course::factory()->create(['status' => CourseStatus::Published]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk();
    }

    public function test_course_detail_shows_course_info(): void
    {
        $course = Course::factory()->create([
            'title' => 'Test Course',
            'status' => CourseStatus::Published,
        ]);

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertSee('Test Course')
            ->assertSee($course->instructor->name);
    }

    public function test_enrolled_student_sees_continue_button(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $course = Course::factory()->create(['status' => CourseStatus::Published]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Active,
        ]);

        Livewire::actingAs($student)
            ->test(CourseShow::class, ['slug' => $course->slug])
            ->assertSee('Continue Learning');
    }

    public function test_unenrolled_student_sees_enroll_button(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $course = Course::factory()->create([
            'status' => CourseStatus::Published,
            'is_free' => true,
        ]);

        Livewire::actingAs($student)
            ->test(CourseShow::class, ['slug' => $course->slug])
            ->assertSee('Enroll for Free');
    }

    public function test_student_can_enroll_in_free_course(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $course = Course::factory()->create([
            'status' => CourseStatus::Published,
            'is_free' => true,
            'price' => 0,
        ]);

        Livewire::actingAs($student)
            ->test(CourseShow::class, ['slug' => $course->slug])
            ->call('enroll')
            ->assertSee('Continue Learning');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_draft_course_returns_404(): void
    {
        $course = Course::factory()->create(['status' => CourseStatus::Draft]);

        $this->get(route('courses.show', $course->slug))
            ->assertNotFound();
    }
}
