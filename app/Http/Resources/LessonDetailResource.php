<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'content' => $this->content,
            'video_url' => $this->video_url,
            'duration_minutes' => $this->duration_minutes,
            'sort_order' => $this->sort_order,
            'is_free_preview' => $this->is_free_preview,
            'is_published' => $this->is_published,
            'video' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('video')),
            'attachments' => $this->whenLoaded('media', fn () => $this->getMedia('attachments')->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->size,
            ])),
            'module' => new ModuleResource($this->whenLoaded('module')),
        ];
    }
}
