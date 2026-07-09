<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up all Laravel caches (config, route, view, event, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting Laravel cleanup...');

        $commands = [
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
            'event:clear',
            'optimize:clear',
        ];

        foreach ($commands as $cmd) {
            $this->call($cmd);
        }

        // Optional: refresh autoload
        $this->info('Running composer dump-autoload -o ...');
        exec('composer dump-autoload -o');

        $this->info('✅ Cleanup complete! All caches cleared and autoload refreshed.');
        return Command::SUCCESS;
    }
}
