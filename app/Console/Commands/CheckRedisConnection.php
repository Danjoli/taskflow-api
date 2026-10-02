<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Throwable;

class CheckRedisConnection extends Command
{
    protected $signature = 'redis:check {connection=default}';

    protected $description = 'Check whether a configured Redis connection is available';

    public function handle(): int
    {
        $connection = (string) $this->argument('connection');

        try {
            $response = Redis::connection($connection)->ping();
        } catch (Throwable $exception) {
            $this->error("Redis connection [{$connection}] failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Redis connection [{$connection}] is available ({$response}).");

        return self::SUCCESS;
    }
}
