<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('does not expose sensitive or undeclared user attributes', function () {
    $user = User::factory()->create();
    $user->setAttribute('internal_only', 'secret');
    Sanctum::actingAs($user);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'timezone',
                    'deadline_notifications_enabled',
                    'created_at',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonMissingPath('data.user.password')
        ->assertJsonMissingPath('data.user.remember_token')
        ->assertJsonMissingPath('data.user.internal_only');
});

it('does not allow task ownership through mass assignment', function () {
    $task = new Task;

    expect($task->isFillable('user_id'))->toBeFalse()
        ->and($task->isFillable('title'))->toBeTrue();
});

it('isolates all tenant resource collections by authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownedProject = Project::factory()->for($user)->create();
    $ownedCategory = Category::factory()->for($user)->create();
    $ownedTag = Tag::factory()->for($user)->create();
    $ownedTask = Task::factory()->for($user)->create();

    $otherProject = Project::factory()->for($otherUser)->create();
    $otherCategory = Category::factory()->for($otherUser)->create();
    $otherTag = Tag::factory()->for($otherUser)->create();
    $otherTask = Task::factory()->for($otherUser)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/projects')
        ->assertOk()
        ->assertJsonFragment(['id' => $ownedProject->id])
        ->assertJsonMissing(['id' => $otherProject->id]);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonFragment(['id' => $ownedCategory->id])
        ->assertJsonMissing(['id' => $otherCategory->id]);

    $this->getJson('/api/tags')
        ->assertOk()
        ->assertJsonFragment(['id' => $ownedTag->id])
        ->assertJsonMissing(['id' => $otherTag->id]);

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonFragment(['id' => $ownedTask->id])
        ->assertJsonMissing(['id' => $otherTask->id]);
});

it('denies cross-account reads writes and deletes for tenant resources', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $resources = [
        'projects' => Project::factory()->for($owner)->create(),
        'categories' => Category::factory()->for($owner)->create(),
        'tags' => Tag::factory()->for($owner)->create(),
        'tasks' => Task::factory()->for($owner)->create(),
    ];

    Sanctum::actingAs($attacker);

    foreach ($resources as $endpoint => $resource) {
        $this->getJson("/api/{$endpoint}/{$resource->id}")
            ->assertForbidden();
        $this->patchJson("/api/{$endpoint}/{$resource->id}", ['name' => 'stolen'])
            ->assertForbidden();
        $this->deleteJson("/api/{$endpoint}/{$resource->id}")
            ->assertForbidden();
    }
});
