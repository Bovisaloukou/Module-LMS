<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'enrollment_id' => $this->enrollment_id,
            'total_lessons' => $this->total_lessons,
            'completed_lessons' => $this->completed_lessons,
            'percentage' => (float) $this->percentage,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'last_lesson_id' => $this->last_lesson_id,
            'last_accessed_at' => $this->last_accessed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'lesson_progress' => LessonProgressResource::collection($this->whenLoaded('lessonProgress')),
        ];
    }
}
