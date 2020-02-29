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
        \App\Console\Commands\SendCode::class
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
        $schedule->command('visit_summary')->dailyAt('10:00');//->everyTenMinutes()->between('12:00', '23:59');
        $schedule->command('photographer_ranking')->everyTenMinutes()->between('12:00', '23:59');
        $schedule->command('view_summary')->dailyAt('10:05')->mondays();//->hourly()->between('12:00', '23:59');
        $schedule->command('silent_activation')->dailyAt('19:55');//->hourly();
        $schedule->command('send_code')->hourly();
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
