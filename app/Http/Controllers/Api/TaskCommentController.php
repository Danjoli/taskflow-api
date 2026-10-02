<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TaskCommentController extends Controller
{
    public function index(
        Request $request,
        Task $task
    ): AnonymousResourceCollection {
        Gate::authorize('view', $task);

        $comments = $task->comments()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(20);

        return CommentResource::collection($comments);
    }

    public function store(
        StoreCommentRequest $request,
        Task $task
    ): JsonResponse {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return (new CommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(
        Request $request,
        Task $task,
        Comment $comment
    ): Response {
        abort_unless($comment->task_id === $task->id, 404);
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
