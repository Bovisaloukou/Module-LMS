<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'short_description' => $this->short_description,
            'price' => $this->price,
            'is_free' => $this->is_free,
            'level' => $this->level,
            'language' => $this->language,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'thumbnail' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('thumbnail')),
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
