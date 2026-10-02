<?php

declare(strict_types=1);

use App\Jobs\SendTaskDeadlineReminder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('dispatches reminders at eight in each enabled users timezone', function () {
    CarbonImmutable::setTestNow('2026-10-02 11:00:00 UTC');
    Queue::fake();

    $enabled = User::factory()->create(['timezone' => 'America/Sao_Paulo']);
    $disabled = User::factory()->create([
        'timezone' => 'America/Sao_Paulo',
        'deadline_notifications_enabled' => false,
    ]);
    $wrongTimezone = User::factory()->create(['timezone' => 'UTC']);

    $task = Task::factory()->for($enabled)->create([
        'due_date' => '2026-10-03',
        'status' => 'pending',
    ]);
    Task::factory()->for($enabled)->create([
        'due_date' => '2026-10-03',
        'status' => 'completed',
    ]);
    Task::factory()->for($disabled)->create(['due_date' => '2026-10-03']);
    Task::factory()->for($wrongTimezone)->create(['due_date' => '2026-10-03']);

    $this->artisan('tasks:dispatch-deadline-reminders')->assertSuccessful();

    Queue::assertPushed(
        SendTaskDeadlineReminder::class,
        fn (SendTaskDeadlineReminder $job) => $job->taskId === $task->id
            && $job->dueDate === '2026-10-03'
    );
    Queue::assertPushed(SendTaskDeadlineReminder::class, 1);
});

it('sends each task deadline reminder only once', function () {
    Notification::fake();
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'due_date' => '2026-10-03',
        'status' => 'in_progress',
    ]);
    $job = new SendTaskDeadlineReminder($task->id, '2026-10-03');

    $job->handle();
    $job->handle();

    Notification::assertSentToTimes($user, TaskDeadlineReminder::class, 1);
    $this->assertDatabaseCount('task_deadline_notifications', 1);
    expect($task->deadlineNotifications()->firstOrFail()->sent_at)->not->toBeNull();
});

it('rechecks task eligibility when the reminder job runs', function () {
    Notification::fake();
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'due_date' => '2026-10-03',
        'status' => 'completed',
    ]);

    (new SendTaskDeadlineReminder($task->id, '2026-10-03'))->handle();

    Notification::assertNothingSent();
    $this->assertDatabaseCount('task_deadline_notifications', 0);
});
