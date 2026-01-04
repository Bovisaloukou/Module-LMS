<?php

namespace Tests\Feature\Api\Quiz;

use App\Enums\EnrollmentStatus;
use App\Models\Answer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Course $course;

    private Quiz $quiz;

    private Question $question1;

    private Question $question2;

    private Answer $correctAnswer1;

    private Answer $correctAnswer2;

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

        $this->quiz = Quiz::factory()->create([
            'course_id' => $this->course->id,
            'pass_percentage' => 50,
            'max_attempts' => 3,
        ]);

        $this->question1 = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'points' => 1,
            'sort_order' => 0,
        ]);
        $this->correctAnswer1 = Answer::factory()->correct()->create([
            'question_id' => $this->question1->id,
            'sort_order' => 0,
        ]);
        Answer::factory()->create([
            'question_id' => $this->question1->id,
            'sort_order' => 1,
        ]);

        $this->question2 = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'points' => 1,
            'sort_order' => 1,
        ]);
        $this->correctAnswer2 = Answer::factory()->correct()->create([
            'question_id' => $this->question2->id,
            'sort_order' => 0,
        ]);
        Answer::factory()->create([
            'question_id' => $this->question2->id,
            'sort_order' => 1,
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);
    }

    public function test_student_can_view_quiz(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/quizzes/'.$this->quiz->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $this->quiz->id)
            ->assertJsonPath('data.title', $this->quiz->title)
            ->assertJsonPath('data.pass_percentage', $this->quiz->pass_percentage)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'questions' => [['id', 'question_text', 'answers']]],
            ]);
    }

    public function test_unenrolled_student_cannot_view_quiz(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');
        Sanctum::actingAs($otherStudent);

        $response = $this->getJson('/api/quizzes/'.$this->quiz->id);

        $response->assertStatus(403);
    }

    public function test_student_can_start_quiz_attempt(): void
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');

        $response->assertOk()
            ->assertJsonPath('data.quiz_id', $this->quiz->id)
            ->assertJsonStructure(['data' => ['id', 'quiz_id', 'started_at']]);

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_student_can_submit_quiz_and_pass(): void
    {
        Sanctum::actingAs($this->student);

        $startResponse = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');
        $attemptId = $startResponse->json('data.id');

        $response = $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $this->correctAnswer1->id],
                ['question_id' => $this->question2->id, 'answer_id' => $this->correctAnswer2->id],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.total_points', 2)
            ->assertJsonPath('data.earned_points', 2);
    }

    public function test_student_can_submit_quiz_and_fail(): void
    {
        Sanctum::actingAs($this->student);

        $wrongAnswer = Answer::where('question_id', $this->question1->id)
            ->where('is_correct', false)
            ->first();

        $startResponse = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');
        $attemptId = $startResponse->json('data.id');

        $response = $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $wrongAnswer->id],
                ['question_id' => $this->question2->id, 'answer_id' => $wrongAnswer->id],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.passed', false)
            ->assertJsonPath('data.earned_points', 0);
    }

    public function test_student_cannot_submit_already_completed_attempt(): void
    {
        Sanctum::actingAs($this->student);

        $startResponse = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');
        $attemptId = $startResponse->json('data.id');

        $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $this->correctAnswer1->id],
                ['question_id' => $this->question2->id, 'answer_id' => $this->correctAnswer2->id],
            ],
        ]);

        $response = $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $this->correctAnswer1->id],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'This attempt has already been submitted.']);
    }

    public function test_student_cannot_exceed_max_attempts(): void
    {
        Sanctum::actingAs($this->student);

        $this->quiz->update(['max_attempts' => 1]);

        $startResponse = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');
        $attemptId = $startResponse->json('data.id');

        $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $this->correctAnswer1->id],
                ['question_id' => $this->question2->id, 'answer_id' => $this->correctAnswer2->id],
            ],
        ]);

        $response = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');

        $response->assertStatus(422)
            ->assertJson(['message' => 'Maximum attempts reached for this quiz.']);
    }

    public function test_student_can_view_attempt_results(): void
    {
        Sanctum::actingAs($this->student);

        $startResponse = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');
        $attemptId = $startResponse->json('data.id');

        $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $this->correctAnswer1->id],
                ['question_id' => $this->question2->id, 'answer_id' => $this->correctAnswer2->id],
            ],
        ]);

        $response = $this->getJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/results');

        $response->assertOk()
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.passed', true)
            ->assertJsonStructure([
                'data' => ['id', 'score', 'passed', 'answers' => [['question_id', 'is_correct', 'points_earned']]],
            ]);
    }

    public function test_student_can_list_attempts(): void
    {
        Sanctum::actingAs($this->student);

        $startResponse = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');
        $attemptId = $startResponse->json('data.id');

        $this->postJson('/api/quizzes/'.$this->quiz->id.'/attempts/'.$attemptId.'/submit', [
            'answers' => [
                ['question_id' => $this->question1->id, 'answer_id' => $this->correctAnswer1->id],
                ['question_id' => $this->question2->id, 'answer_id' => $this->correctAnswer2->id],
            ],
        ]);

        $response = $this->getJson('/api/quizzes/'.$this->quiz->id.'/attempts');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_cannot_access_quiz(): void
    {
        $response = $this->getJson('/api/quizzes/'.$this->quiz->id);

        $response->assertUnauthorized();
    }

    public function test_student_cannot_start_unpublished_quiz(): void
    {
        Sanctum::actingAs($this->student);

        $this->quiz->update(['is_published' => false]);

        $response = $this->postJson('/api/quizzes/'.$this->quiz->id.'/start');

        $response->assertStatus(422)
            ->assertJson(['message' => 'This quiz is not available.']);
    }
}
