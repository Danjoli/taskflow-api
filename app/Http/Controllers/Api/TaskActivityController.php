<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskActivityResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskActivityController extends Controller
{
    public function index(
        Request $request,
        Task $task
    ): AnonymousResourceCollection {
        Gate::authorize('view', $task);

        $activities = $task->activities()
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return TaskActivityResource::collection($activities);
    }
}
