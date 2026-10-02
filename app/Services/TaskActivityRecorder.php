<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskActivityType;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;

class TaskActivityRecorder
{
    private const TRACKED_FIELDS = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'project_id',
        'category_id',
        'parent_id',
    ];

    public function recordCreated(Task $task, User $actor): TaskActivity
    {
        $changes = [];

        foreach (self::TRACKED_FIELDS as $field) {
            $changes[$field] = [
                'old' => null,
                'new' => $task->getRawOriginal($field),
            ];
        }

        $changes['tag_ids'] = [
            'old' => [],
            'new' => $task->tags()->orderBy('tags.id')->pluck('tags.id')->all(),
        ];

        return $task->activities()->create([
            'user_id' => $actor->id,
            'type' => TaskActivityType::Created,
            'changes' => $changes,
        ]);
    }

    public function recordUpdated(
        Task $task,
        User $actor,
        array $beforeAttributes,
        ?array $beforeTagIds = null
    ): ?TaskActivity {
        $changes = [];

        foreach ($beforeAttributes as $field => $oldValue) {
            $newValue = $task->getRawOriginal($field);

            if ($oldValue !== $newValue) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        if ($beforeTagIds !== null) {
            sort($beforeTagIds);
            $newTagIds = $task->tags()
                ->orderBy('tags.id')
                ->pluck('tags.id')
                ->all();

            if ($beforeTagIds !== $newTagIds) {
                $changes['tag_ids'] = [
                    'old' => $beforeTagIds,
                    'new' => $newTagIds,
                ];
            }
        }

        if ($changes === []) {
            return null;
        }

        return $task->activities()->create([
            'user_id' => $actor->id,
            'type' => TaskActivityType::Updated,
            'changes' => $changes,
        ]);
    }
}
