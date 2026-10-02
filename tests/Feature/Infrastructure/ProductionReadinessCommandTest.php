<?php

declare(strict_types=1);

it('passes when production configuration is safe', function () {
    $this->app->detectEnvironment(fn (): string => 'production');
    config([
        'app.debug' => false,
        'app.key' => 'base64:production-key',
        'app.url' => 'https://api.example.test',
        'database.default' => 'pgsql',
        'cache.default' => 'redis',
        'queue.default' => 'redis',
        'logging.default' => 'stack',
        'logging.channels.stack.channels' => ['stderr'],
        'session.secure' => true,
        'mail.default' => 'smtp',
    ]);

    $this->artisan('app:production-check')
        ->expectsOutputToContain('Production configuration is ready.')
        ->assertSuccessful();
});

it('fails and identifies unsafe production configuration', function () {
    config([
        'app.debug' => true,
        'app.key' => null,
        'app.url' => 'http://localhost',
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'logging.default' => 'single',
        'session.secure' => false,
        'mail.default' => 'log',
    ]);

    $this->artisan('app:production-check')
        ->expectsOutputToContain('Production configuration is not ready.')
        ->assertFailed();
});
