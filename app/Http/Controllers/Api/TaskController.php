<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskActivityRecorder $activityRecorder
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $tasks = $request->user()
            ->tasks()
            ->with('tags')
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                isset($filters['priority']),
                fn ($query) => $query->where('priority', $filters['priority'])
            )
            ->when(
                isset($filters['due_date']),
                fn ($query) => $query->where('due_date', $filters['due_date'])
            )
            ->when(
                isset($filters['project_id']),
                fn ($query) => $query->where('project_id', $filters['project_id'])
            )
            ->when(
                isset($filters['category_id']),
                fn ($query) => $query->where('category_id', $filters['category_id'])
            )
            ->when(
                isset($filters['tag_id']),
                fn ($query) => $query->whereHas(
                    'tags',
                    fn ($tagQuery) => $tagQuery->whereKey($filters['tag_id'])
                )
            )
            ->when(
                isset($filters['parent_id']),
                fn ($query) => $query->where('parent_id', $filters['parent_id'])
            )
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate(15)
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $task = DB::transaction(function () use ($request, $validated, $tagIds) {
            $task = $request->user()->tasks()->create($validated);
            $task->tags()->sync($tagIds);
            $task->refresh();

            $this->activityRecorder->recordCreated($task, $request->user());

            return $task->load('tags');
        });

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task->load('tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $validated = $request->validated();
        $hasTagIds = array_key_exists('tag_ids', $validated);
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        DB::transaction(function () use (
            $request,
            $task,
            $validated,
            $hasTagIds,
            $tagIds
        ): void {
            $beforeAttributes = array_intersect_key(
                $task->getRawOriginal(),
                $validated
            );
            $beforeTagIds = $hasTagIds
                ? $task->tags()->pluck('tags.id')->all()
                : null;

            $task->update($validated);

            if ($hasTagIds) {
                $task->tags()->sync($tagIds);
            }

            $task->refresh();
            $this->activityRecorder->recordUpdated(
                $task,
                $request->user(),
                $beforeAttributes,
                $beforeTagIds
            );
        });

        return new TaskResource($task->load('tags'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
