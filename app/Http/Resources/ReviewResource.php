<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at?->toISOString(),
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
            ],
            'course' => new CourseResource($this->whenLoaded('course')),
        ];
    }
}
