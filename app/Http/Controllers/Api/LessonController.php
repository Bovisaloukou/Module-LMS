<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonDetailResource;
use App\Models\Lesson;

/**
 * @group Learning
 *
 * APIs for enrolled students accessing lesson content.
 */
class LessonController extends Controller
{
    /**
     * Show Lesson
     *
     * Get full lesson content including media URLs.
     *
     * @authenticated
     */
    public function show(Lesson $lesson): LessonDetailResource
    {
        $lesson->load(['module', 'media']);

        return new LessonDetailResource($lesson);
    }
}
