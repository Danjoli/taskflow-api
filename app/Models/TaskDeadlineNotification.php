<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDeadlineNotification extends Model
{
    protected $fillable = ['user_id', 'due_date', 'sent_at'];

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
            'due_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }
}
