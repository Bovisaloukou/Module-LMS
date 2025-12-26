<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'price_paid' => $this->price_paid,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at,
            'completed_at' => $this->completed_at,
            'expires_at' => $this->expires_at,
            'course' => new CourseResource($this->whenLoaded('course')),
            'student' => new UserResource($this->whenLoaded('student')),
            'created_at' => $this->created_at,
        ];
    }
}
