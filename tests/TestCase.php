<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = $app['config']->get('database.default');

        $database = $app['config']->get(
            'database.connections.pgsql.database'
        );

        if (
            $connection !== 'pgsql' ||
            $database !== 'taskflow_test'
        ) {
            throw new RuntimeException(
                'Tests must use the dedicated taskflow_test PostgreSQL database.'
            );
        }

        return $app;
    }
}
