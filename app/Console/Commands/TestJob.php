<?php

namespace App\Console\Commands;

use App\Jobs\JobTest;
use Illuminate\Console\Command;
use Log;

class TestJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        JobTest::dispatch();
        Log::info('Command TestJob succes');
        return 0;
    }
}
