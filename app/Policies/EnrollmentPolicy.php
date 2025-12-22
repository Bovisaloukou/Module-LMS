<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $enrollment->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['student', 'admin']);
    }
}
