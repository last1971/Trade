<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRancidPrices;
use Illuminate\Console\Command;

class ClearRancidPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prices:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Rancid Prices';

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
        ProcessRancidPrices::dispatch();
        return Command::SUCCESS;
    }
}
