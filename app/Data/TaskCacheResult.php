<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Task;

readonly class TaskCacheResult
{
    public function __construct(
        public Task $task,
        public bool $hit
    ) {}
}
