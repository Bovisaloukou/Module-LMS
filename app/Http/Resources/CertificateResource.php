<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'certificate_number' => $this->certificate_number,
            'course_id' => $this->course_id,
            'enrollment_id' => $this->enrollment_id,
            'issued_at' => $this->issued_at?->toISOString(),
            'download_url' => route('certificates.download', $this->certificate_number),
            'course' => new CourseResource($this->whenLoaded('course')),
        ];
    }
}
