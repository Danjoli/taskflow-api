<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication for category endpoints', function () {
    $category = Category::factory()->create();

    $this->getJson('/api/categories')->assertUnauthorized();
    $this->postJson('/api/categories', ['name' => 'Work'])->assertUnauthorized();
    $this->getJson("/api/categories/{$category->id}")->assertUnauthorized();
    $this->patchJson("/api/categories/{$category->id}", ['name' => 'Home'])->assertUnauthorized();
    $this->deleteJson("/api/categories/{$category->id}")->assertUnauthorized();
});

it('lists only the authenticated users categories alphabetically', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Category::factory()->for($user)->create(['name' => 'Work']);
    Category::factory()->for($user)->create(['name' => 'Home']);
    Category::factory()->for($otherUser)->create(['name' => 'Private']);

    Sanctum::actingAs($user);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Home')
        ->assertJsonPath('data.1.name', 'Work');
});

it('creates a category for the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/categories', ['name' => 'Work'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Work')
        ->assertJsonMissingPath('data.user_id');

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => 'Work',
    ]);
});

it('enforces category name uniqueness per user', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Work']);
    Category::factory()->create(['name' => 'Work']);
    Sanctum::actingAs($user);

    $this->postJson('/api/categories', ['name' => 'Work'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('allows different users to use the same category name', function () {
    Category::factory()->create(['name' => 'Work']);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/categories', ['name' => 'Work'])
        ->assertCreated();
});

it('allows owners to view and update a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work']);
    Sanctum::actingAs($user);

    $this->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Work');

    $this->patchJson("/api/categories/{$category->id}", ['name' => 'Career'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Career');
});

it('forbids access to another users category', function () {
    $category = Category::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/categories/{$category->id}")->assertForbidden();
    $this->patchJson("/api/categories/{$category->id}", ['name' => 'No'])->assertForbidden();
    $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();
});

it('deletes a category owned by the authenticated user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('validates category attributes and prohibits assigning an owner', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/categories', [
        'name' => str_repeat('A', 101),
        'user_id' => 999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'user_id']);
});
