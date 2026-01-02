<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CoursesCatalog;
use App\Livewire\CourseShow;
use App\Livewire\Dashboard;
use App\Livewire\Home;
use App\Livewire\Learning\CourseLearn;
use App\Livewire\Learning\QuizAttempt;
use App\Livewire\MyCertificates;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', Home::class)->name('home');
Route::get('/courses', CoursesCatalog::class)->name('courses.catalog');
Route::get('/courses/{slug}', CourseShow::class)->name('courses.show');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

// Student pages (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('student.dashboard');
    Route::get('/learn/{slug}', CourseLearn::class)->name('student.learn');
    Route::get('/quiz/{quizId}', QuizAttempt::class)->name('student.quiz');
    Route::get('/certificates', MyCertificates::class)->name('student.certificates');
});
