<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to list projects', function () {
    $this->getJson('/api/projects')
        ->assertUnauthorized();
});

it('lists only projects belonging to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Project::factory()
        ->count(3)
        ->for($user)
        ->create();

    Project::factory()
        ->count(2)
        ->for($otherUser)
        ->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('paginates projects with 15 items per page', function () {
    $user = User::factory()->create();

    Project::factory()
        ->count(20)
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.per_page', 15);
});
