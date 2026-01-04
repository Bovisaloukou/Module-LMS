<?php

namespace Tests\Feature\Api\Review;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Course $course;

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

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);
    }

    public function test_can_list_course_reviews_publicly(): void
    {
        Review::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'rating' => 5,
            'comment' => 'Excellent course!',
            'is_approved' => true,
        ]);

        $response = $this->getJson('/api/courses/'.$this->course->id.'/reviews');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_unapproved_reviews_not_shown_publicly(): void
    {
        Review::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'rating' => 1,
            'comment' => 'Bad',
            'is_approved' => false,
        ]);

        $response = $this->getJson('/api/courses/'.$this->course->id.'/reviews');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_enrolled_student_can_create_review(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/courses/'.$this->course->id.'/reviews', [
            'rating' => 4,
            'comment' => 'Great course, learned a lot!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'Great course, learned a lot!');

        $this->assertDatabaseHas('reviews', [
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'rating' => 4,
        ]);
    }

    public function test_unenrolled_student_cannot_create_review(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        Sanctum::actingAs($otherStudent);

        $response = $this->postJson('/api/courses/'.$this->course->id.'/reviews', [
            'rating' => 5,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_review_same_course_twice(): void
    {
        Sanctum::actingAs($this->student);

        Review::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        $response = $this->postJson('/api/courses/'.$this->course->id.'/reviews', [
            'rating' => 3,
        ]);

        $response->assertStatus(409);
    }

    public function test_student_can_update_own_review(): void
    {
        Sanctum::actingAs($this->student);

        $review = Review::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'rating' => 3,
            'comment' => 'Okay',
            'is_approved' => true,
        ]);

        $response = $this->putJson('/api/courses/'.$this->course->id.'/reviews/'.$review->id, [
            'rating' => 5,
            'comment' => 'Actually amazing!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Actually amazing!');
    }

    public function test_student_cannot_update_others_review(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        Sanctum::actingAs($otherStudent);

        $review = Review::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        $response = $this->putJson('/api/courses/'.$this->course->id.'/reviews/'.$review->id, [
            'rating' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_student_can_delete_own_review(): void
    {
        Sanctum::actingAs($this->student);

        $review = Review::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        $response = $this->deleteJson('/api/courses/'.$this->course->id.'/reviews/'.$review->id);

        $response->assertOk();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_review_rating_validation(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/courses/'.$this->course->id.'/reviews', [
            'rating' => 6,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_unauthenticated_cannot_create_review(): void
    {
        $response = $this->postJson('/api/courses/'.$this->course->id.'/reviews', [
            'rating' => 5,
        ]);

        $response->assertUnauthorized();
    }
}
