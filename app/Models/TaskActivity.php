<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TaskActivityType $type
 * @property array<string, mixed> $changes
 * @property Task $task
 * @property User $user
 */
class TaskActivity extends Model
{
    protected $fillable = ['user_id', 'type', 'changes'];

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'type' => TaskActivityType::class,
            'changes' => 'array',
        ];
    }
}
