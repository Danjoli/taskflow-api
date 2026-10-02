<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('completes an authenticated workflow across related API resources', function () {
    $registration = $this->postJson('/api/register', [
        'name' => 'Integration User',
        'email' => 'integration@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertCreated();

    $token = $registration->json('data.token');
    $headers = ['Authorization' => "Bearer {$token}"];

    $projectId = $this->postJson('/api/projects', [
        'name' => 'Launch',
    ], $headers)->assertCreated()->json('data.id');

    $categoryId = $this->postJson('/api/categories', [
        'name' => 'Engineering',
    ], $headers)->assertCreated()->json('data.id');

    $tagId = $this->postJson('/api/tags', [
        'name' => 'urgent',
    ], $headers)->assertCreated()->json('data.id');

    $taskId = $this->postJson('/api/tasks', [
        'title' => 'Ship API',
        'project_id' => $projectId,
        'category_id' => $categoryId,
        'tag_ids' => [$tagId],
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.tags.0.id', $tagId)
        ->json('data.id');

    $this->postJson("/api/tasks/{$taskId}/comments", [
        'body' => 'Ready for review',
    ], $headers)->assertCreated();

    $this->patchJson("/api/tasks/{$taskId}", [
        'status' => 'completed',
    ], $headers)->assertOk()->assertJsonPath('data.status', 'completed');

    $this->getJson("/api/tasks?project_id={$projectId}&category_id={$categoryId}&tag_id={$tagId}", $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $taskId);

    $this->getJson("/api/tasks/{$taskId}/activities", $headers)
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->deleteJson("/api/tasks/{$taskId}", [], $headers)
        ->assertNoContent();

    $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    $this->assertDatabaseMissing('comments', ['task_id' => $taskId]);
    $this->assertDatabaseMissing('task_activities', ['task_id' => $taskId]);
});

it('does not persist partial task data when related ownership validation fails', function () {
    $user = User::factory()->create();
    $foreignTag = Tag::factory()->create();
    $token = $user->createToken('integration-test')->plainTextToken;

    $this->postJson('/api/tasks', [
        'title' => 'Must not persist',
        'tag_ids' => [$foreignTag->id],
    ], ['Authorization' => "Bearer {$token}"])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tag_ids.0');

    $this->assertDatabaseMissing('tasks', [
        'user_id' => $user->id,
        'title' => 'Must not persist',
    ]);
    $this->assertDatabaseCount('task_activities', 0);
    $this->assertDatabaseCount('tag_task', 0);
});

it('rejects invalid bearer tokens and renders API routing errors as JSON', function () {
    $this->withToken('invalid-token')
        ->getJson('/api/me')
        ->assertUnauthorized()
        ->assertHeader('content-type', 'application/json');

    $this->getJson('/api/unknown-endpoint')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');

    $this->putJson('/api/login')
        ->assertMethodNotAllowed()
        ->assertHeader('content-type', 'application/json');
});

it('has PostgreSQL uniqueness constraints that close validation race windows', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $constraints = collect(DB::select(<<<'SQL'
        SELECT tables.relname AS table_name, constraints.contype AS type
        FROM pg_constraint AS constraints
        INNER JOIN pg_class AS tables ON tables.oid = constraints.conrelid
        WHERE tables.relname IN ('users', 'categories', 'tags')
          AND constraints.contype = 'u'
        SQL))
        ->pluck('type', 'table_name');

    expect($constraints)
        ->toHaveKeys(['users', 'categories', 'tags'])
        ->each->toBe('u');
});
