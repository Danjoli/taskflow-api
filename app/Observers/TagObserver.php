<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tag;
use App\Services\TaskCache;

class TagObserver
{
    public function __construct(private readonly TaskCache $cache) {}

    public function updating(Tag $tag): void
    {
        $this->invalidateTasks($tag);
    }

    public function deleting(Tag $tag): void
    {
        $this->invalidateTasks($tag);
    }

    private function invalidateTasks(Tag $tag): void
    {
        $this->cache->forgetMany($tag->user_id, $tag->tasks()->pluck('id'));
    }
}
