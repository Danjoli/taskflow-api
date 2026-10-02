<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskActivityType: string
{
    case Created = 'created';
    case Updated = 'updated';
}
