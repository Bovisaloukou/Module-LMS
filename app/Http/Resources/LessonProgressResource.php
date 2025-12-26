<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'watch_time_seconds' => $this->watch_time_seconds,
            'completed_at' => $this->completed_at?->toISOString(),
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
        ];
    }
}
