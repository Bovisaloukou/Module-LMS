<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'answer_id' => $this->answer_id,
            'text_answer' => $this->text_answer,
            'is_correct' => $this->is_correct,
            'points_earned' => $this->points_earned,
            'question' => new QuestionResource($this->whenLoaded('question')),
        ];
    }
}
