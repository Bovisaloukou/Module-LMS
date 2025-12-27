<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\ProgressStatus;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class ProgressService
{
    public function __construct(
        private CertificateService $certificateService
    ) {}

    public function markLessonComplete(User $student, Lesson $lesson, Enrollment $enrollment): LessonProgress
    {
        $lessonProgress = $this->getOrCreateLessonProgress($student, $lesson, $enrollment);

        if ($lessonProgress->status === ProgressStatus::Completed) {
            return $lessonProgress;
        }

        $lessonProgress->update([
            'status' => ProgressStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->recalculateCourseProgress($student, $enrollment);

        return $lessonProgress->fresh();
    }

    public function updateWatchTime(User $student, Lesson $lesson, Enrollment $enrollment, int $seconds): LessonProgress
    {
        $lessonProgress = $this->getOrCreateLessonProgress($student, $lesson, $enrollment);

        $lessonProgress->update([
            'watch_time_seconds' => $lessonProgress->watch_time_seconds + $seconds,
            'status' => $lessonProgress->status === ProgressStatus::NotStarted
                ? ProgressStatus::InProgress
                : $lessonProgress->status,
        ]);

        $this->updateLastAccessed($student, $enrollment, $lesson);

        return $lessonProgress->fresh();
    }

    public function getCourseProgress(User $student, Enrollment $enrollment): CourseProgress
    {
        return $this->getOrCreateCourseProgress($student, $enrollment);
    }

    public function getLessonProgress(User $student, Enrollment $enrollment): \Illuminate\Database\Eloquent\Collection
    {
        return LessonProgress::where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->get();
    }

    private function getOrCreateLessonProgress(User $student, Lesson $lesson, Enrollment $enrollment): LessonProgress
    {
        return LessonProgress::firstOrCreate(
            [
                'student_id' => $student->id,
                'lesson_id' => $lesson->id,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'status' => ProgressStatus::NotStarted,
                'watch_time_seconds' => 0,
            ]
        );
    }

    private function getOrCreateCourseProgress(User $student, Enrollment $enrollment): CourseProgress
    {
        $course = $enrollment->course;
        $totalLessons = $course->lessons()->count();

        return CourseProgress::firstOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'total_lessons' => $totalLessons,
                'completed_lessons' => 0,
                'percentage' => 0,
                'status' => ProgressStatus::NotStarted,
            ]
        );
    }

    private function recalculateCourseProgress(User $student, Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        $totalLessons = $course->lessons()->count();

        $completedLessons = LessonProgress::where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->where('status', ProgressStatus::Completed)
            ->count();

        $percentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100, 2)
            : 0;

        $status = match (true) {
            $completedLessons === 0 => ProgressStatus::NotStarted,
            $completedLessons >= $totalLessons => ProgressStatus::Completed,
            default => ProgressStatus::InProgress,
        };

        $courseProgress = $this->getOrCreateCourseProgress($student, $enrollment);
        $courseProgress->update([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'percentage' => $percentage,
            'status' => $status,
            'completed_at' => $status === ProgressStatus::Completed ? now() : null,
        ]);

        if ($status === ProgressStatus::Completed && $enrollment->status === EnrollmentStatus::Active) {
            $enrollment->complete();
            $this->certificateService->generate($student, $course, $enrollment);
        }
    }

    private function updateLastAccessed(User $student, Enrollment $enrollment, Lesson $lesson): void
    {
        $courseProgress = $this->getOrCreateCourseProgress($student, $enrollment);
        $courseProgress->update([
            'last_lesson_id' => $lesson->id,
            'last_accessed_at' => now(),
            'status' => $courseProgress->status === ProgressStatus::NotStarted
                ? ProgressStatus::InProgress
                : $courseProgress->status,
        ]);
    }
}
