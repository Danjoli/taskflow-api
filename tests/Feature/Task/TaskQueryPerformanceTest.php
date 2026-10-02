<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('lists paginated tasks with tags using a constant query budget', function () {
    $user = User::factory()->create();
    $tags = Tag::factory()->count(2)->for($user)->create();

    Task::factory()
        ->count(20)
        ->for($user)
        ->create()
        ->each(fn (Task $task) => $task->tags()->attach($tags));

    Sanctum::actingAs($user);
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonCount(2, 'data.0.tags')
        ->assertJsonPath('meta.total', 20);

    expect(DB::getQueryLog())->toHaveCount(3);
});

it('creates the composite indexes used by scoped task queries', function () {
    $indexes = collect(DB::select(
        "SELECT indexname FROM pg_indexes WHERE tablename = 'tasks'"
    ))->pluck('indexname');

    expect($indexes)->toContain(
        'tasks_user_created_id_index',
        'tasks_user_priority_index',
        'tasks_user_project_index',
        'tasks_user_category_index',
        'tasks_user_parent_index'
    );
});
