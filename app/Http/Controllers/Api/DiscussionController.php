<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiscussionReplyResource;
use App\Http\Resources\DiscussionResource;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Discussions
 *
 * APIs for lesson discussions (Q&A).
 */
class DiscussionController extends Controller
{
    /**
     * List Discussions
     *
     * Get all discussions for a lesson.
     *
     * @authenticated
     */
    public function index(Request $request, Lesson $lesson): AnonymousResourceCollection|JsonResponse
    {
        if (! $this->isEnrolledInLessonCourse($request, $lesson)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $discussions = Discussion::where('lesson_id', $lesson->id)
            ->with('user:id,name')
            ->withCount('replies')
            ->latest()
            ->paginate(15);

        return DiscussionResource::collection($discussions);
    }

    /**
     * Show Discussion
     *
     * Get a discussion with all its replies.
     *
     * @authenticated
     */
    public function show(Request $request, Discussion $discussion): DiscussionResource|JsonResponse
    {
        $lesson = $discussion->lesson;

        if (! $this->isEnrolledInLessonCourse($request, $lesson)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $discussion->load(['user:id,name', 'replies.user:id,name']);
        $discussion->loadCount('replies');

        return new DiscussionResource($discussion);
    }

    /**
     * Create Discussion
     *
     * Start a new discussion on a lesson.
     *
     * @authenticated
     *
     * @bodyParam title string required Discussion title. Example: How does dependency injection work?
     * @bodyParam body string required Discussion body. Example: I'm confused about...
     */
    public function store(Request $request, Lesson $lesson): DiscussionResource|JsonResponse
    {
        if (! $this->isEnrolledInLessonCourse($request, $lesson)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $discussion = Discussion::create([
            'lesson_id' => $lesson->id,
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $discussion->load('user:id,name');

        return (new DiscussionResource($discussion))->response()->setStatusCode(201);
    }

    /**
     * Reply to Discussion
     *
     * Add a reply to an existing discussion.
     *
     * @authenticated
     *
     * @bodyParam body string required Reply body. Example: You can use constructor injection...
     */
    public function reply(Request $request, Discussion $discussion): DiscussionReplyResource|JsonResponse
    {
        $lesson = $discussion->lesson;

        if (! $this->isEnrolledInLessonCourse($request, $lesson)) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $reply->load('user:id,name');

        return (new DiscussionReplyResource($reply))->response()->setStatusCode(201);
    }

    /**
     * Mark as Resolved
     *
     * Mark a discussion as resolved. Only the discussion author or instructor can do this.
     *
     * @authenticated
     */
    public function resolve(Request $request, Discussion $discussion): DiscussionResource|JsonResponse
    {
        $user = $request->user();
        $course = $discussion->lesson->module->course;

        if ($discussion->user_id !== $user->id
            && $course->instructor_id !== $user->id
            && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $discussion->update(['is_resolved' => true]);

        return new DiscussionResource($discussion->fresh()->load('user:id,name'));
    }

    /**
     * Mark Reply as Solution
     *
     * Mark a reply as the solution. Only the discussion author or instructor can do this.
     *
     * @authenticated
     */
    public function markSolution(Request $request, Discussion $discussion, DiscussionReply $reply): DiscussionReplyResource|JsonResponse
    {
        if ($reply->discussion_id !== $discussion->id) {
            return response()->json(['message' => 'Reply not found for this discussion.'], 404);
        }

        $user = $request->user();
        $course = $discussion->lesson->module->course;

        if ($discussion->user_id !== $user->id
            && $course->instructor_id !== $user->id
            && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $discussion->replies()->update(['is_solution' => false]);
        $reply->update(['is_solution' => true]);
        $discussion->update(['is_resolved' => true]);

        return new DiscussionReplyResource($reply->fresh()->load('user:id,name'));
    }

    private function isEnrolledInLessonCourse(Request $request, Lesson $lesson): bool
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return true;
        }

        $courseId = $lesson->module->course_id;

        if ($lesson->module->course->instructor_id === $user->id) {
            return true;
        }

        return Enrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();
    }
}
