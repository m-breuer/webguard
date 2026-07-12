<?php

declare(strict_types=1);

namespace App\Enums;

enum RegionalConsensusStatus: string
{
    case HEALTHY = 'healthy';
    case LOCALIZED = 'localized';
    case REGIONAL = 'regional';
    case GLOBAL = 'global';
    case UNKNOWN = 'unknown';
}
