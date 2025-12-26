<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'question_text' => $this->question_text,
            'points' => $this->points,
            'sort_order' => $this->sort_order,
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
