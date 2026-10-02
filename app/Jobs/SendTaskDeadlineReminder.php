<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskDeadlineReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTaskDeadlineReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $taskId,
        public readonly string $dueDate
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $task = Task::query()->with('user')->find($this->taskId);

        if (
            $task === null
            || ! $task->user->deadline_notifications_enabled
            || $task->due_date?->toDateString() !== $this->dueDate
            || ! in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true)
        ) {
            return;
        }

        $delivery = $task->deadlineNotifications()->firstOrCreate(
            ['due_date' => $this->dueDate],
            ['user_id' => $task->user_id]
        );

        if ($delivery->sent_at !== null) {
            return;
        }

        $task->user->notify(new TaskDeadlineReminder($task));
        $delivery->update(['sent_at' => now()]);
    }
}
