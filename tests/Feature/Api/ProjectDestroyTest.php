<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to delete a project', function () {
    $project = Project::factory()->create();

    $this->deleteJson("/api/projects/{$project->id}")
        ->assertUnauthorized();
});

it('allows the owner to delete their project', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/projects/{$project->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

it('forbids deleting another users project', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $project = Project::factory()
        ->for($otherUser)
        ->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/projects/{$project->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'user_id' => $otherUser->id,
    ]);
});

it('returns not found when deleting a nonexistent project', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson('/api/projects/999999999')
        ->assertNotFound();
});
