<?php

namespace App\Console;

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
        \App\Console\Commands\VisitSummary::class,
        \App\Console\Commands\PhotographerRanking::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('visit_summary')->everyTenMinutes()->between('12:00', '23:59');//->dailyAt('10:00');
        $schedule->command('photographer_ranking')->everyTenMinutes()->between('12:00', '23:59');
        $schedule->command('view_summary')->hourly()->between('12:00', '23:59');//->dailyAt('10:05')->mondays();
//        $schedule->command('silent_activation')->hourly()->between('12:00', '23:59');//->dailyAt('19:55');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
