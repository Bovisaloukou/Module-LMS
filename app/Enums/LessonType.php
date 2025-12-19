<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Text = 'text';
    case Pdf = 'pdf';
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Text => 'Text',
            self::Pdf => 'PDF',
            self::Quiz => 'Quiz',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Video => 'info',
            self::Text => 'gray',
            self::Pdf => 'warning',
            self::Quiz => 'success',
        };
    }
}
