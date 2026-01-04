<?php

namespace Tests\Feature\Api\Instructor;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorDashboardTest extends TestCase
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

    public function test_instructor_can_get_dashboard_stats(): void
    {
        Sanctum::actingAs($this->instructor);

        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => CourseStatus::Published,
        ]);

        $student = User::factory()->create();
        $student->assignRole('student');

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'price_paid' => 49.99,
        ]);

        Review::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'rating' => 4,
            'is_approved' => true,
        ]);

        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.total_courses', 1)
            ->assertJsonPath('data.published_courses', 1)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.total_revenue', 49.99)
            ->assertJsonPath('data.average_rating', 4);
    }

    public function test_dashboard_stats_only_count_own_courses(): void
    {
        Sanctum::actingAs($this->instructor);

        Course::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('instructor');
        Course::factory()->count(3)->create([
            'instructor_id' => $otherInstructor->id,
        ]);

        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.total_courses', 1);
    }

    public function test_dashboard_stats_exclude_refunded_enrollments(): void
    {
        Sanctum::actingAs($this->instructor);

        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        Enrollment::factory()->create([
            'course_id' => $course->id,
            'price_paid' => 50.00,
            'status' => EnrollmentStatus::Active,
        ]);

        Enrollment::factory()->create([
            'course_id' => $course->id,
            'price_paid' => 50.00,
            'status' => EnrollmentStatus::Refunded,
        ]);

        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.total_revenue', 50);
    }

    public function test_dashboard_stats_with_no_courses(): void
    {
        Sanctum::actingAs($this->instructor);

        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.total_courses', 0)
            ->assertJsonPath('data.published_courses', 0)
            ->assertJsonPath('data.total_students', 0)
            ->assertJsonPath('data.total_revenue', 0)
            ->assertJsonPath('data.average_rating', null);
    }

    public function test_dashboard_stats_exclude_unapproved_reviews(): void
    {
        Sanctum::actingAs($this->instructor);

        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $student = User::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        Review::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        Review::factory()->create([
            'student_id' => User::factory()->create()->id,
            'course_id' => $course->id,
            'enrollment_id' => Enrollment::factory()->create(['course_id' => $course->id])->id,
            'rating' => 1,
            'is_approved' => false,
        ]);

        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.average_rating', 5);
    }

    public function test_student_cannot_access_instructor_dashboard(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_instructor_dashboard(): void
    {
        $response = $this->getJson('/api/instructor/dashboard');

        $response->assertUnauthorized();
    }
}
