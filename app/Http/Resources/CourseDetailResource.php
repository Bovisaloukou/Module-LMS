<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price' => $this->price,
            'is_free' => $this->is_free,
            'level' => $this->level,
            'language' => $this->language,
            'duration_minutes' => $this->duration_minutes,
            'requirements' => $this->requirements,
            'what_you_learn' => $this->what_you_learn,
            'status' => $this->status,
            'thumbnail' => $this->getFirstMediaUrl('thumbnail'),
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'modules' => ModuleResource::collection($this->whenLoaded('modules')),
            'average_rating' => $this->average_rating,
            'student_count' => $this->student_count,
            'lessons_count' => $this->lessons_count,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
