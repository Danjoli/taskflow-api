<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('records task creation with the authenticated actor', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Audited task',
        'status' => 'pending',
    ])->assertCreated();

    $activity = Task::query()->firstOrFail()->activities()->firstOrFail();

    expect($activity->type->value)->toBe('created')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->changes['title']['new'])->toBe('Audited task');
});

it('records changed task fields with old and new values', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'title' => 'Before',
        'status' => 'pending',
    ]);
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => 'After',
        'status' => 'completed',
    ])->assertOk();

    $activity = $task->activities()->firstOrFail();
    expect($activity->type->value)->toBe('updated')
        ->and($activity->changes['title'])->toBe([
            'old' => 'Before',
            'new' => 'After',
        ])
        ->and($activity->changes['status'])->toBe([
            'old' => 'pending',
            'new' => 'completed',
        ]);
});

it('records tag changes and skips updates without real changes', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Same']);
    $tag = Tag::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", ['title' => 'Same'])
        ->assertOk();
    expect($task->activities()->count())->toBe(0);

    $this->patchJson("/api/tasks/{$task->id}", ['tag_ids' => [$tag->id]])
        ->assertOk();

    expect($task->activities()->firstOrFail()->changes['tag_ids'])
        ->toBe(['old' => [], 'new' => [$tag->id]]);
});

it('allows only the task owner to list immutable activity history', function () {
    $user = User::factory()->create(['name' => 'Daniel']);
    $task = Task::factory()->for($user)->create();
    $task->activities()->create([
        'user_id' => $user->id,
        'type' => 'updated',
        'changes' => ['title' => ['old' => 'A', 'new' => 'B']],
    ]);

    Sanctum::actingAs($user);
    $this->getJson("/api/tasks/{$task->id}/activities")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'updated')
        ->assertJsonPath('data.0.actor.name', 'Daniel');

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/tasks/{$task->id}/activities")
        ->assertForbidden();
});

it('requires authentication to view activity history', function () {
    $task = Task::factory()->create();

    $this->getJson("/api/tasks/{$task->id}/activities")
        ->assertUnauthorized();
});
