<?php

namespace App\Console;

use App\Jobs\GetFixture;
use App\Jobs\AddCommission;
use App\Jobs\LeaderBoardJobs;
use App\Jobs\PendingPayment;
use App\Jobs\CalculateEarnings;
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
        $schedule->job(GetFixture::class)->everyFifteenMinutes();
        $schedule->job(LeaderBoardJobs::class)->dailyAt('02:00');
        $schedule->job(AddCommission::class)->dailyAt('01:00');
        $schedule->job(CalculateEarnings::class)->dailyAt('04:00');
        $schedule->job(PendingPayment::class)->everyTenMinutes();
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
