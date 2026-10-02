<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a task with owned tags and returns them', function () {
    $user = User::factory()->create();
    $tags = Tag::factory()->count(2)->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Tagged task',
        'tag_ids' => $tags->modelKeys(),
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data.tags');

    $task = Task::query()->where('user_id', $user->id)->firstOrFail();
    expect($task->tags()->pluck('tags.id')->all())
        ->toEqualCanonicalizing($tags->modelKeys());
});

it('rejects tags owned by another user and duplicate tag ids', function () {
    $user = User::factory()->create();
    $ownTag = Tag::factory()->for($user)->create();
    $otherTag = Tag::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Invalid tags',
        'tag_ids' => [$ownTag->id, $ownTag->id, $otherTag->id],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tag_ids.1', 'tag_ids.2']);
});

it('replaces clears and preserves task tags', function () {
    $user = User::factory()->create();
    $originalTags = Tag::factory()->count(2)->for($user)->create();
    $newTag = Tag::factory()->for($user)->create();
    $task = Task::factory()->for($user)->create();
    $task->tags()->attach($originalTags);
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", ['title' => 'Updated'])
        ->assertOk()
        ->assertJsonCount(2, 'data.tags');

    $this->patchJson("/api/tasks/{$task->id}", ['tag_ids' => [$newTag->id]])
        ->assertOk()
        ->assertJsonCount(1, 'data.tags')
        ->assertJsonPath('data.tags.0.id', $newTag->id);

    $this->patchJson("/api/tasks/{$task->id}", ['tag_ids' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data.tags');
});

it('rejects replacing task tags with another users tag', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $otherTag = Tag::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'tag_ids' => [$otherTag->id],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tag_ids.0']);
});

it('returns and filters tasks by tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();
    $otherTag = Tag::factory()->for($user)->create();
    $task = Task::factory()->for($user)->create();
    $otherTask = Task::factory()->for($user)->create();
    $task->tags()->attach($tag);
    $otherTask->tags()->attach($otherTag);
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.tags.0.id', $tag->id);

    $this->getJson("/api/tasks?tag_id={$tag->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $task->id)
        ->assertJsonPath('meta.total', 1);
});

it('rejects filtering by another users tag', function () {
    $otherTag = Tag::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tasks?tag_id={$otherTag->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tag_id']);
});
