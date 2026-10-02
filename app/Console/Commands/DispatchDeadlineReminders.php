<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Jobs\SendTaskDeadlineReminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DispatchDeadlineReminders extends Command
{
    protected $signature = 'tasks:dispatch-deadline-reminders';

    protected $description = 'Dispatch deadline reminders due at 08:00 in each user timezone';

    public function handle(): int
    {
        User::query()
            ->where('deadline_notifications_enabled', true)
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $localNow = CarbonImmutable::now($user->timezone);

                    if ($localNow->hour !== 8) {
                        continue;
                    }

                    $dueDate = $localNow->addDay()->toDateString();

                    $user->tasks()
                        ->whereDate('due_date', $dueDate)
                        ->whereIn('status', [
                            TaskStatus::Pending->value,
                            TaskStatus::InProgress->value,
                        ])
                        ->pluck('id')
                        ->each(fn (int $taskId) => SendTaskDeadlineReminder::dispatch(
                            $taskId,
                            $dueDate
                        ));
                }
            });

        return self::SUCCESS;
    }
}
