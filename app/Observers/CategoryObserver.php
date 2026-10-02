<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Services\TaskCache;

class CategoryObserver
{
    public function __construct(private readonly TaskCache $cache) {}

    public function deleting(Category $category): void
    {
        $this->cache->forgetMany(
            $category->user_id,
            $category->tasks()->pluck('id')
        );
    }
}
