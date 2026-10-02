<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TaskCacheResult;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class TaskCache
{
    public function find(User $user, int $taskId): TaskCacheResult
    {
        $key = $this->key($user->id, $taskId);
        $cached = Cache::get($key);

        if ($cached instanceof Task) {
            return new TaskCacheResult($cached, true);
        }

        $task = Task::query()->with('tags')->findOrFail($taskId);
        Gate::forUser($user)->authorize('view', $task);

        Cache::put($key, $task, config('taskflow.task_cache_ttl'));

        return new TaskCacheResult($task, false);
    }

    public function forget(int $userId, int $taskId): void
    {
        Cache::forget($this->key($userId, $taskId));
    }

    /** @param iterable<int> $taskIds */
    public function forgetMany(int $userId, iterable $taskIds): void
    {
        foreach ($taskIds as $taskId) {
            $this->forget($userId, (int) $taskId);
        }
    }

    private function key(int $userId, int $taskId): string
    {
        return "users:{$userId}:tasks:{$taskId}:v1";
    }
}
