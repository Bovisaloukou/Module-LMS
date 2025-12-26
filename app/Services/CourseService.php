<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CourseService
{
    public function list(array $filters = []): Builder
    {
        $query = Course::query()
            ->published()
            ->with(['instructor', 'category', 'media']);

        if (! empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['is_free'])) {
            $query->where('is_free', true);
        }

        $sortBy = $filters['sort'] ?? 'latest';

        return match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'title' => $query->orderBy('title', 'asc'),
            default => $query->latest('published_at'),
        };
    }

    public function create(User $instructor, array $data): Course
    {
        $data['instructor_id'] = $instructor->id;

        return Course::create($data)->fresh();
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);

        return $course->fresh();
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }

    public function publish(Course $course): Course
    {
        $course->update([
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        return $course->fresh();
    }

    public function archive(Course $course): Course
    {
        $course->update([
            'status' => CourseStatus::Archived,
        ]);

        return $course->fresh();
    }
}
