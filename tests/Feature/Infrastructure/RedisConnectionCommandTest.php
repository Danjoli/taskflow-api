<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;

it('reports a successful redis connection', function () {
    $connection = Mockery::mock();
    $connection->shouldReceive('ping')->once()->andReturn('PONG');

    Redis::shouldReceive('connection')
        ->once()
        ->with('default')
        ->andReturn($connection);

    $this->artisan('redis:check')
        ->expectsOutput('Redis connection [default] is available (PONG).')
        ->assertSuccessful();
});

it('reports a failed redis connection without exposing credentials', function () {
    Redis::shouldReceive('connection')
        ->once()
        ->with('cache')
        ->andThrow(new RuntimeException('Connection refused'));

    $this->artisan('redis:check cache')
        ->expectsOutput('Redis connection [cache] failed: Connection refused')
        ->assertFailed();
});
