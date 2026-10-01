<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to view a project', function () {
    $project = Project::factory()->create();

    $this->getJson("/api/projects/{$project->id}")
        ->assertUnauthorized();
});

it('allows the owner to view their project', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create([
            'name' => 'TaskFlow Backend',
        ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.name', 'TaskFlow Backend');
});

it('forbids viewing a project owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = Project::factory()
        ->for($otherUser)
        ->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/projects/{$project->id}")
        ->assertForbidden();
});

it('returns not found for a nonexistent project', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/projects/999999999')
        ->assertNotFound();
});
