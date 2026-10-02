<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication for tag endpoints', function () {
    $tag = Tag::factory()->create();

    $this->getJson('/api/tags')->assertUnauthorized();
    $this->postJson('/api/tags', ['name' => 'urgent'])->assertUnauthorized();
    $this->getJson("/api/tags/{$tag->id}")->assertUnauthorized();
    $this->patchJson("/api/tags/{$tag->id}", ['name' => 'later'])->assertUnauthorized();
    $this->deleteJson("/api/tags/{$tag->id}")->assertUnauthorized();
});

it('lists only the authenticated users tags alphabetically', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'urgent']);
    Tag::factory()->for($user)->create(['name' => 'backend']);
    Tag::factory()->create(['name' => 'private']);
    Sanctum::actingAs($user);

    $this->getJson('/api/tags')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'backend')
        ->assertJsonPath('data.1.name', 'urgent');
});

it('creates tags and enforces name uniqueness per user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tags', ['name' => 'urgent'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'urgent')
        ->assertJsonMissingPath('data.user_id');

    $this->postJson('/api/tags', ['name' => 'urgent'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('allows different users to use the same tag name', function () {
    Tag::factory()->create(['name' => 'urgent']);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tags', ['name' => 'urgent'])->assertCreated();
});

it('allows owners to view update and delete tags', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'urgent']);
    Sanctum::actingAs($user);

    $this->getJson("/api/tags/{$tag->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'urgent');

    $this->patchJson("/api/tags/{$tag->id}", ['name' => 'important'])
        ->assertOk()
        ->assertJsonPath('data.name', 'important');

    $this->deleteJson("/api/tags/{$tag->id}")->assertNoContent();
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

it('forbids access to another users tag', function () {
    $tag = Tag::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tags/{$tag->id}")->assertForbidden();
    $this->patchJson("/api/tags/{$tag->id}", ['name' => 'no'])->assertForbidden();
    $this->deleteJson("/api/tags/{$tag->id}")->assertForbidden();
});

it('validates tag attributes and prohibits assigning an owner', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tags', [
        'name' => str_repeat('A', 101),
        'user_id' => 999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'user_id']);
});
