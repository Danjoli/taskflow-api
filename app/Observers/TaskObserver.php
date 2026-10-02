<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskCache;

class TaskObserver
{
    public function __construct(private readonly TaskCache $cache) {}

    public function updated(Task $task): void
    {
        $this->cache->forget($task->user_id, $task->id);
    }

    public function deleting(Task $task): void
    {
        $ids = $task->subtasks()->pluck('id')->push($task->id);
        $this->cache->forgetMany($task->user_id, $ids);
    }
}
