<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTaskCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $taskId,
        public readonly int $userId
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        Log::info('Task creation processed.', [
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Task creation processing failed.', [
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
