<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Single Choice',
            self::MultipleChoice => 'Multiple Choice',
            self::TrueFalse => 'True / False',
            self::ShortAnswer => 'Short Answer',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SingleChoice => 'info',
            self::MultipleChoice => 'warning',
            self::TrueFalse => 'success',
            self::ShortAnswer => 'gray',
        };
    }
}
