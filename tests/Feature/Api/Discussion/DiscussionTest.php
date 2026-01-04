<?php

namespace Tests\Feature\Api\Discussion;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscussionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Course $course;

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

        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);
    }

    public function test_enrolled_student_can_list_discussions(): void
    {
        Sanctum::actingAs($this->student);

        Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Test question',
            'body' => 'How does this work?',
        ]);

        $response = $this->getJson('/api/lessons/'.$this->lesson->id.'/discussions');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test question');
    }

    public function test_unenrolled_student_cannot_list_discussions(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        Sanctum::actingAs($otherStudent);

        $response = $this->getJson('/api/lessons/'.$this->lesson->id.'/discussions');

        $response->assertForbidden();
    }

    public function test_enrolled_student_can_create_discussion(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/discussions', [
            'title' => 'Help with dependency injection',
            'body' => 'Can someone explain how constructor injection works?',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Help with dependency injection');

        $this->assertDatabaseHas('discussions', [
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_can_view_discussion_with_replies(): void
    {
        Sanctum::actingAs($this->student);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'A question',
            'body' => 'Details here',
        ]);

        DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $this->instructor->id,
            'body' => 'Here is the answer.',
        ]);

        $response = $this->getJson('/api/discussions/'.$discussion->id);

        $response->assertOk()
            ->assertJsonPath('data.title', 'A question')
            ->assertJsonCount(1, 'data.replies');
    }

    public function test_enrolled_student_can_reply_to_discussion(): void
    {
        Sanctum::actingAs($this->student);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Question',
            'body' => 'Body',
        ]);

        $response = $this->postJson('/api/discussions/'.$discussion->id.'/replies', [
            'body' => 'Thanks, that helps!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Thanks, that helps!');

        $this->assertDatabaseHas('discussion_replies', [
            'discussion_id' => $discussion->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_instructor_can_reply_to_discussion(): void
    {
        Sanctum::actingAs($this->instructor);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Help needed',
            'body' => 'I am stuck',
        ]);

        $response = $this->postJson('/api/discussions/'.$discussion->id.'/replies', [
            'body' => 'Let me help you with that.',
        ]);

        $response->assertStatus(201);
    }

    public function test_discussion_author_can_resolve(): void
    {
        Sanctum::actingAs($this->student);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Resolved question',
            'body' => 'Got it working',
        ]);

        $response = $this->postJson('/api/discussions/'.$discussion->id.'/resolve');

        $response->assertOk()
            ->assertJsonPath('data.is_resolved', true);
    }

    public function test_instructor_can_resolve_discussion(): void
    {
        Sanctum::actingAs($this->instructor);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Question',
            'body' => 'Body',
        ]);

        $response = $this->postJson('/api/discussions/'.$discussion->id.'/resolve');

        $response->assertOk()
            ->assertJsonPath('data.is_resolved', true);
    }

    public function test_other_student_cannot_resolve_discussion(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $otherCourse = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
        ]);
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        Sanctum::actingAs($otherStudent);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Question',
            'body' => 'Body',
        ]);

        $response = $this->postJson('/api/discussions/'.$discussion->id.'/resolve');

        $response->assertForbidden();
    }

    public function test_can_mark_reply_as_solution(): void
    {
        Sanctum::actingAs($this->student);

        $discussion = Discussion::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'title' => 'Question',
            'body' => 'Body',
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $this->instructor->id,
            'body' => 'This is the solution.',
        ]);

        $response = $this->postJson('/api/discussions/'.$discussion->id.'/replies/'.$reply->id.'/solution');

        $response->assertOk()
            ->assertJsonPath('data.is_solution', true);

        $this->assertDatabaseHas('discussions', [
            'id' => $discussion->id,
            'is_resolved' => true,
        ]);
    }

    public function test_create_discussion_requires_title_and_body(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/discussions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body']);
    }

    public function test_unauthenticated_cannot_create_discussion(): void
    {
        $response = $this->postJson('/api/lessons/'.$this->lesson->id.'/discussions', [
            'title' => 'Test',
            'body' => 'Body',
        ]);

        $response->assertUnauthorized();
    }
}
