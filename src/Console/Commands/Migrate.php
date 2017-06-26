<?php

namespace CoasterCms\Console\Commands;

use Illuminate\Console\Command;

class Migrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coaster:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs database migrations for Coaster.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('migrate',
            ['--path' => 'vendor/web-feet/coasterframework/database/migrations']
        );
    }

}
