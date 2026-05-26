<?php

declare(strict_types=1);

namespace App\Console\Commands\Sitemap;

use App\Support\SitemapPages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Generate the sitemap.')]
#[Signature('sitemap:generate')]
class GenerateSitemapCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        SitemapPages::sitemap()->writeToFile(public_path('sitemap.xml'));

        return Command::SUCCESS;
    }
}
