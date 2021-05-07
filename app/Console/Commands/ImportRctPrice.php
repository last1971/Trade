<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRctPrice;
use Illuminate\Console\Command;

class ImportRctPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:rct';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Radiotechtrade prices';

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
        ProcessRctPrice::dispatch();
        return 0;
    }
}
