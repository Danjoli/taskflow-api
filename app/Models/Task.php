<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property CarbonInterface|null $due_date
 * @property User $user
 * @property Collection<int, Tag> $tags
 */
#[Fillable([
    'title',
    'description',
    'status',
    'priority',
    'due_date',
    'project_id',
    'category_id',
    'parent_id',
])]
#[Hidden([])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Task, $this> */
    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<TaskActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    /** @return HasMany<TaskDeadlineNotification, $this> */
    public function deadlineNotifications(): HasMany
    {
        return $this->hasMany(TaskDeadlineNotification::class);
    }

    public function isOverdue(?CarbonInterface $today = null): bool
    {
        $today ??= CarbonImmutable::today(config('app.timezone'));

        return $this->due_date !== null
            && $this->due_date->lt($today)
            && in_array(
                $this->status,
                [TaskStatus::Pending, TaskStatus::InProgress],
                true
            );
    }

    public function scopeOverdue(
        Builder $query,
        CarbonInterface $today
    ): Builder {
        return $query
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereIn('status', [
                TaskStatus::Pending->value,
                TaskStatus::InProgress->value,
            ]);
    }

    public function scopeDueSoon(
        Builder $query,
        CarbonInterface $today,
        int $days
    ): Builder {
        return $query
            ->whereBetween('due_date', [
                $today->toDateString(),
                $today->addDays($days)->toDateString(),
            ])
            ->whereIn('status', [
                TaskStatus::Pending->value,
                TaskStatus::InProgress->value,
            ]);
    }
}
