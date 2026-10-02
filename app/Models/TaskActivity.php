<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    protected $fillable = ['user_id', 'type', 'changes'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

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
