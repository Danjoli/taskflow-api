<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a task in an owned category and exposes the association', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Categorized task',
        'category_id' => $category->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.category_id', $category->id);

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);
});

it('rejects assigning a task to another users category', function () {
    $user = User::factory()->create();
    $otherCategory = Category::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Invalid category',
        'category_id' => $otherCategory->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

it('moves and removes a task category', function () {
    $user = User::factory()->create();
    $firstCategory = Category::factory()->for($user)->create();
    $secondCategory = Category::factory()->for($user)->create();
    $task = Task::factory()->for($user)->forCategory($firstCategory)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'category_id' => $secondCategory->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.category_id', $secondCategory->id);

    $this->patchJson("/api/tasks/{$task->id}", [
        'category_id' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.category_id', null);
});

it('preserves the category when category id is not submitted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $task = Task::factory()->for($user)->forCategory($category)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", ['title' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('data.category_id', $category->id);
});

it('rejects moving a task to another users category', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $otherCategory = Category::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'category_id' => $otherCategory->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

it('returns and filters tasks by category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $otherCategory = Category::factory()->for($user)->create();
    $task = Task::factory()->for($user)->forCategory($category)->create();
    Task::factory()->for($user)->forCategory($otherCategory)->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.category_id', $category->id);

    $this->getJson("/api/tasks?category_id={$category->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $task->id)
        ->assertJsonPath('meta.total', 1);
});

it('rejects filtering by another users category', function () {
    $otherCategory = Category::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tasks?category_id={$otherCategory->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});
