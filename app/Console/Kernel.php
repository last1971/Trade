<?php

namespace App\Console;

use App\Jobs\ProcessDanPrice;
use App\Jobs\ProcessMarsPrice;
use App\Jobs\ProcessPositronPrice;
use App\Jobs\ProcessRancidPrices;
use App\Jobs\ProcessRctPrice;
use App\Jobs\ProcessSeaTronicPrice;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->job(new ProcessMarsPrice)->dailyAt('10:00');
        $schedule->job(new ProcessRancidPrices)->dailyAt('03:00');
        $schedule->job(new ProcessDanPrice)->dailyAt('21:00');
        $schedule->job(new ProcessRctPrice)->dailyAt('23:00');
        $schedule->job(new ProcessSeaTronicPrice)->dailyAt('07:00');
        $schedule->job(new ProcessPositronPrice)->dailyAt('08:00');
        $schedule->command('make:searchname')->weekly()->sundays()->at('12:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
