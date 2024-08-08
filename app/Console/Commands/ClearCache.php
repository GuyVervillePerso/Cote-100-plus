<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pacc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the application cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Call the built-in cache:clear command
        Artisan::call('cache:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->info('Application cache cleared successfully.');

        return 0;
    }
}
