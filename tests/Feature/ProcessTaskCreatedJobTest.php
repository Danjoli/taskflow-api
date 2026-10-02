<?php

declare(strict_types=1);

use App\Jobs\ProcessTaskCreated;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('dispatches task creation processing after creating a task', function () {
    Queue::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/tasks', ['title' => 'Queued task'])
        ->assertCreated();

    Queue::assertPushed(
        ProcessTaskCreated::class,
        fn (ProcessTaskCreated $job) => $job->taskId === $response->json('data.id')
            && $job->userId === $user->id
    );
});

it('defines bounded retries backoff and timeout', function () {
    $job = new ProcessTaskCreated(taskId: 10, userId: 20);

    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(30)
        ->and($job->backoff())->toBe([10, 30, 60]);
});

it('logs successful processing with identifiers', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Task creation processed.', [
            'task_id' => 10,
            'user_id' => 20,
        ]);

    (new ProcessTaskCreated(taskId: 10, userId: 20))->handle();
});

it('logs terminal failures with safe context', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Task creation processing failed.', [
            'task_id' => 10,
            'user_id' => 20,
            'exception' => 'Unavailable',
        ]);

    (new ProcessTaskCreated(taskId: 10, userId: 20))
        ->failed(new RuntimeException('Unavailable'));
});
