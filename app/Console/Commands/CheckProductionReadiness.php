<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:production-check')]
#[Description('Validate production configuration before deployment')]
class CheckProductionReadiness extends Command
{
    public function handle(): int
    {
        $hasFailures = false;

        foreach ($this->checks() as $description => $passed) {
            $status = $passed ? '<info>PASS</info>' : '<error>FAIL</error>';
            $this->line("[{$status}] {$description}");
            $hasFailures = $hasFailures || ! $passed;
        }

        if ($hasFailures) {
            $this->newLine();
            $this->error('Production configuration is not ready.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Production configuration is ready.');

        return self::SUCCESS;
    }

    /** @return array<string, bool> */
    private function checks(): array
    {
        $logChannel = config('logging.default');
        $stackChannels = config('logging.channels.stack.channels', []);

        return [
            'APP_ENV is production' => app()->isProduction(),
            'APP_DEBUG is disabled' => config('app.debug') === false,
            'APP_KEY is configured' => filled(config('app.key')),
            'APP_URL uses HTTPS' => Str::startsWith((string) config('app.url'), 'https://'),
            'PostgreSQL is the default database' => config('database.default') === 'pgsql',
            'cache uses a shared production store' => in_array(
                config('cache.default'),
                ['database', 'redis', 'memcached', 'dynamodb'],
                true
            ),
            'queue uses an asynchronous connection' => in_array(
                config('queue.default'),
                ['database', 'redis', 'sqs'],
                true
            ),
            'logs are written to stderr' => $logChannel === 'stderr'
                || ($logChannel === 'stack'
                    && is_array($stackChannels)
                    && in_array('stderr', $stackChannels, true)),
            'session cookies require HTTPS' => config('session.secure') === true,
            'mail is not using a development driver' => ! in_array(
                config('mail.default'),
                ['array', 'log'],
                true
            ),
        ];
    }
}
