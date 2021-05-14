<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDanPrice;
use Illuminate\Console\Command;

class ImportDanPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:dan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Dan prices';

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
        ProcessDanPrice::dispatch();
        return 0;
    }
}
