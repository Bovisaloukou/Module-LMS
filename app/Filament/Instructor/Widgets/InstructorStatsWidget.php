<?php

namespace App\Filament\Instructor\Widgets;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InstructorStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $instructorId = auth()->id();

        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        $totalCourses = $courseIds->count();
        $publishedCourses = Course::where('instructor_id', $instructorId)
            ->where('status', CourseStatus::Published)
            ->count();

        $totalStudents = Enrollment::whereIn('course_id', $courseIds)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->distinct('student_id')
            ->count('student_id');

        $totalRevenue = Enrollment::whereIn('course_id', $courseIds)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->sum('price_paid');

        $avgRating = Review::whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->avg('rating');

        return [
            Stat::make('Total Courses', $totalCourses)
                ->description($publishedCourses.' published')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('Total Students', $totalStudents)
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Total Revenue', '$'.number_format($totalRevenue, 2))
                ->icon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Average Rating', $avgRating ? number_format($avgRating, 1).'/5' : 'N/A')
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
