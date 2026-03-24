<?php

declare(strict_types=1);

namespace App\Console\Commands\Sitemap;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

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
        Sitemap::create()
            ->add(Url::create(route('welcome')))
            ->add(Url::create(route('monitoring-locations')))
            ->add(Url::create(route('imprint')))
            ->writeToFile(public_path('sitemap.xml'));

        return Command::SUCCESS;
    }
}
