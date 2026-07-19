<?php

declare(strict_types=1);

namespace App\Enums;

enum MaintenanceWindowRecurrence: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}
