<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('sorts tasks by title in ascending order', function () {
    $user = User::factory()->create();

    Task::factory()->for($user)->create(['title' => 'Zebra']);
    Task::factory()->for($user)->create(['title' => 'Banana']);
    Task::factory()->for($user)->create(['title' => 'Abacaxi']);

    Sanctum::actingAs($user);

    $response = $this->getJson(
        '/api/tasks?sort_by=title&sort_direction=asc'
    );

    $response->assertOk();

    expect(
        collect($response->json('data'))->pluck('title')->all()
    )->toBe([
        'Abacaxi',
        'Banana',
        'Zebra',
    ]);
});

it('sorts tasks by due date in ascending order', function () {
    $user = User::factory()->create();

    Task::factory()->for($user)->create([
        'due_date' => '2026-10-20',
    ]);

    Task::factory()->for($user)->create([
        'due_date' => '2026-10-05',
    ]);

    Task::factory()->for($user)->create([
        'due_date' => '2026-10-15',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(
        '/api/tasks?sort_by=due_date&sort_direction=asc'
    );

    $response->assertOk();

    expect(
        collect($response->json('data'))->pluck('due_date')->all()
    )->toBe([
        '2026-10-05',
        '2026-10-15',
        '2026-10-20',
    ]);
});

it('rejects invalid sorting parameters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(
        '/api/tasks?sort_by=password&sort_direction=invalid'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sort_by',
            'sort_direction',
        ]);
});

it('preserves sorting parameters in pagination links', function () {
    $user = User::factory()->create();

    Task::factory()
        ->count(20)
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $response = $this->getJson(
        '/api/tasks?sort_by=title&sort_direction=asc&page=1'
    );

    $response
        ->assertOk()
        ->assertJsonCount(15, 'data');

    expect($response->json('links.next'))
        ->toContain('sort_by=title')
        ->toContain('sort_direction=asc')
        ->toContain('page=2');
});
