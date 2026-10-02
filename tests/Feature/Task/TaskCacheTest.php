<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Cache::clear();
});

it('caches individual task reads and reports hits', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertHeader('X-Task-Cache', 'MISS');

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertHeader('X-Task-Cache', 'HIT');
});

it('isolates cached tasks by authenticated user', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    Sanctum::actingAs($owner);
    $this->getJson("/api/tasks/{$task->id}")->assertOk();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/tasks/{$task->id}")->assertForbidden();
});

it('invalidates a cached task after updates', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Before']);
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")->assertOk();
    $this->getJson("/api/tasks/{$task->id}")
        ->assertHeader('X-Task-Cache', 'HIT');

    $this->patchJson("/api/tasks/{$task->id}", ['title' => 'After'])
        ->assertOk();

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertHeader('X-Task-Cache', 'MISS')
        ->assertJsonPath('data.title', 'After');
});

it('invalidates task caches when tag data changes', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'old']);
    $task = Task::factory()->for($user)->create();
    $task->tags()->attach($tag);
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")->assertOk();
    $this->patchJson("/api/tags/{$tag->id}", ['name' => 'new'])
        ->assertOk();

    $this->getJson("/api/tasks/{$task->id}")
        ->assertHeader('X-Task-Cache', 'MISS')
        ->assertJsonPath('data.tags.0.name', 'new');
});

it('invalidates task caches when a category is deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $task = Task::factory()->for($user)->forCategory($category)->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")->assertOk();
    $this->deleteJson("/api/categories/{$category->id}")
        ->assertNoContent();

    $this->getJson("/api/tasks/{$task->id}")
        ->assertHeader('X-Task-Cache', 'MISS')
        ->assertJsonPath('data.category_id', null);
});

it('uses a configurable positive cache ttl', function () {
    expect(config('taskflow.task_cache_ttl'))->toBeGreaterThan(0);
});
