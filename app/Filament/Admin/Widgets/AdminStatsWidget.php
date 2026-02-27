<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', CourseStatus::Published)->count();

        $totalStudents = User::role('student')->count();
        $totalInstructors = User::role('instructor')->count();

        $activeEnrollments = Enrollment::whereIn('status', [
            EnrollmentStatus::Active,
            EnrollmentStatus::Completed,
        ])->count();

        $totalRevenue = Enrollment::whereIn('status', [
            EnrollmentStatus::Active,
            EnrollmentStatus::Completed,
        ])->sum('price_paid');

        $totalCertificates = Certificate::count();

        $avgRating = Review::where('is_approved', true)->avg('rating');

        return [
            Stat::make('Total Courses', $totalCourses)
                ->description($publishedCourses.' published')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('Students', $totalStudents)
                ->description($activeEnrollments.' active enrollments')
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Instructors', $totalInstructors)
                ->icon('heroicon-o-briefcase')
                ->color('info'),

            Stat::make('Revenue', '$'.number_format($totalRevenue, 2))
                ->icon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Certificates Issued', $totalCertificates)
                ->icon('heroicon-o-document-check')
                ->color('success'),

            Stat::make('Average Rating', $avgRating ? number_format($avgRating, 1).'/5' : 'N/A')
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
