<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Project;
use App\Services\TaskCache;

class ProjectObserver
{
    public function __construct(private readonly TaskCache $cache) {}

    public function deleting(Project $project): void
    {
        $this->cache->forgetMany(
            $project->user_id,
            $project->tasks()->pluck('id')
        );
    }
}
