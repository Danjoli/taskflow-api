<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskDeadlineFilter: string
{
    case Overdue = 'overdue';
    case DueSoon = 'due_soon';
}
