<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('uses the dedicated testing database', function () {
    $connection = DB::connection();

    expect($connection->getDriverName())
        ->toBe('pgsql');

    $database = $connection->selectOne(
        'SELECT current_database() AS name'
    );

    expect($database->name)
        ->toBe('taskflow_test');
});
