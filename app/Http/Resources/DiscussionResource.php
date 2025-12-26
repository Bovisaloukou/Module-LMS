<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscussionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'body' => $this->body,
            'is_resolved' => $this->is_resolved,
            'replies_count' => $this->whenCounted('replies'),
            'created_at' => $this->created_at?->toISOString(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'replies' => DiscussionReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
