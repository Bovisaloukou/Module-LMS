<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'score' => $this->score ? (float) $this->score : null,
            'total_points' => $this->total_points,
            'earned_points' => $this->earned_points,
            'passed' => $this->passed,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'answers' => QuizAttemptAnswerResource::collection($this->whenLoaded('attemptAnswers')),
        ];
    }
}
