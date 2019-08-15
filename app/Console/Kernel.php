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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(Commands\Tlr\Notify\StaleServicesCommand::class)
            ->monthlyOn(15, '09:00');

        $schedule->command(Commands\Tlr\Notify\UnactionedReferralsCommand::class)
            ->dailyAt('09:00');

        $schedule->command(Commands\Tlr\Notify\StillUnactionedReferralsCommand::class)
            ->dailyAt('09:00');

        $schedule->command(Commands\Tlr\AutoDelete\AuditsCommand::class)
            ->daily();

        $schedule->command(Commands\Tlr\AutoDelete\PageFeedbacksCommand::class)
            ->daily();

        $schedule->command(Commands\Tlr\AutoDelete\PendingAssignmentFilesCommand::class)
            ->daily();

        $schedule->command(Commands\Tlr\AutoDelete\ReferralsCommand::class)
            ->daily();

        $schedule->command(Commands\Tlr\AutoDelete\ServiceRefreshTokensCommand::class)
            ->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
