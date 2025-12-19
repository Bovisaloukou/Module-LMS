<?php

namespace App\Enums;

enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Beginner => 'success',
            self::Intermediate => 'warning',
            self::Advanced => 'danger',
        };
    }
}
