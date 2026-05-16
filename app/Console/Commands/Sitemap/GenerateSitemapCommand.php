<?php

declare(strict_types=1);

namespace App\Console\Commands\Sitemap;

use App\Support\SitemapPages;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SitemapPages::sitemap()->writeToFile(public_path('sitemap.xml'));

        return Command::SUCCESS;
    }
}
