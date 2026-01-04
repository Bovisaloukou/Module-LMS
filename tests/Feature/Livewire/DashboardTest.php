<?php

namespace Tests\Feature\Livewire;

use App\Enums\EnrollmentStatus;
use App\Livewire\Dashboard;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->student = User::factory()->create();
        $this->student->assignRole('student');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get(route('student.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_shows_enrolled_courses(): void
    {
        $course = Course::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Active,
        ]);

        Livewire::actingAs($this->student)
            ->test(Dashboard::class)
            ->assertSee($course->title);
    }

    public function test_dashboard_shows_correct_stats(): void
    {
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $course1->id,
            'status' => EnrollmentStatus::Active,
        ]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $course2->id,
            'status' => EnrollmentStatus::Completed,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($this->student)
            ->test(Dashboard::class)
            ->assertSeeInOrder(['Active Courses', '1'])
            ->assertSeeInOrder(['Completed', '1']);
    }

    public function test_dashboard_hides_refunded_enrollments(): void
    {
        $course = Course::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Refunded,
        ]);

        Livewire::actingAs($this->student)
            ->test(Dashboard::class)
            ->assertDontSee($course->title);
    }
}
