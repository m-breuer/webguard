<?php

declare(strict_types=1);

namespace App\Console\Commands\Robots;

use App\Support\RobotsTxt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Generate robots.txt.')]
#[Signature('robots:generate')]
class GenerateRobotsCommand extends Command
{
    public function handle(): int
    {
        file_put_contents(public_path('robots.txt'), RobotsTxt::content());

        return Command::SUCCESS;
    }
}
