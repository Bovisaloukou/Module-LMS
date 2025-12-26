<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'duration_minutes' => $this->duration_minutes,
            'sort_order' => $this->sort_order,
            'is_free_preview' => $this->is_free_preview,
            'is_published' => $this->is_published,
        ];
    }
}
