<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModuleResource;
use App\Models\Course;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Learning
 *
 * APIs for enrolled students accessing course content.
 */
class CurriculumController extends Controller
{
    /**
     * Course Curriculum
     *
     * Get full module/lesson tree for an enrolled course.
     *
     * @authenticated
     */
    public function show(Course $course): AnonymousResourceCollection
    {
        $course->load(['modules.lessons']);

        return ModuleResource::collection($course->modules);
    }
}
