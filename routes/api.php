<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CurriculumController;
use App\Http\Controllers\Api\DiscussionController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Api\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Api\Instructor\QuizController as InstructorQuizController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course:slug}', [CourseController::class, 'show']);

// Stripe webhook (no auth, verified via signature)
Route::post('/webhooks/stripe', [PaymentController::class, 'webhook']);

// Public certificate verification
Route::get('/certificates/{certificateNumber}/verify', [CertificateController::class, 'verify']);

// Public course reviews
Route::get('/courses/{course}/reviews', [ReviewController::class, 'index']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Enrollments
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);

    // Learning (enrolled students)
    Route::get('/courses/{course}/curriculum', [CurriculumController::class, 'show']);
    Route::get('/lessons/{lesson}', [LessonController::class, 'show']);

    // Progress tracking
    Route::get('/courses/{course}/progress', [ProgressController::class, 'courseProgress']);
    Route::get('/courses/{course}/progress/lessons', [ProgressController::class, 'lessonProgressIndex']);
    Route::post('/lessons/{lesson}/complete', [ProgressController::class, 'completeLesson']);
    Route::post('/lessons/{lesson}/watch-time', [ProgressController::class, 'updateWatchTime']);

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index']);
    Route::get('/certificates/{certificateNumber}', [CertificateController::class, 'show']);
    Route::get('/certificates/{certificateNumber}/download', [CertificateController::class, 'download'])
        ->name('certificates.download');

    // Reviews
    Route::post('/courses/{course}/reviews', [ReviewController::class, 'store']);
    Route::put('/courses/{course}/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/courses/{course}/reviews/{review}', [ReviewController::class, 'destroy']);

    // Discussions
    Route::get('/lessons/{lesson}/discussions', [DiscussionController::class, 'index']);
    Route::post('/lessons/{lesson}/discussions', [DiscussionController::class, 'store']);
    Route::get('/discussions/{discussion}', [DiscussionController::class, 'show']);
    Route::post('/discussions/{discussion}/replies', [DiscussionController::class, 'reply']);
    Route::post('/discussions/{discussion}/resolve', [DiscussionController::class, 'resolve']);
    Route::post('/discussions/{discussion}/replies/{reply}/solution', [DiscussionController::class, 'markSolution']);

    // Quizzes (enrolled students)
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
    Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start']);
    Route::post('/quizzes/{quiz}/attempts/{attemptId}/submit', [QuizController::class, 'submit']);
    Route::get('/quizzes/{quiz}/attempts/{attemptId}/results', [QuizController::class, 'results']);
    Route::get('/quizzes/{quiz}/attempts', [QuizController::class, 'attempts']);

    // Instructor routes
    Route::middleware('role:instructor,admin')->prefix('instructor')->group(function () {
        Route::get('/dashboard', [InstructorDashboardController::class, 'stats']);

        Route::get('/courses', [InstructorCourseController::class, 'index']);
        Route::post('/courses', [InstructorCourseController::class, 'store']);
        Route::get('/courses/{course}', [InstructorCourseController::class, 'show']);
        Route::put('/courses/{course}', [InstructorCourseController::class, 'update']);
        Route::delete('/courses/{course}', [InstructorCourseController::class, 'destroy']);
        Route::post('/courses/{course}/publish', [InstructorCourseController::class, 'publish']);

        // Instructor quiz management
        Route::get('/courses/{course}/quizzes', [InstructorQuizController::class, 'index']);
        Route::post('/courses/{course}/quizzes', [InstructorQuizController::class, 'store']);
        Route::get('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'show']);
        Route::post('/courses/{course}/quizzes/{quiz}/publish', [InstructorQuizController::class, 'publish']);
        Route::delete('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'destroy']);
    });
});
