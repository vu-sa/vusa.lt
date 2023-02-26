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
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->call(function () {
        //     \App\Actions\Schedulable\MeetingNotifier::notifyDaysLeft(2);
        // })->daily('15:00');

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\MeetingNotifier::notifyOnMeetingUnfinishedStatus();
        // })->days([1, 3, 6])->daily('11:00');

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\ReflectionNotifier::notifyUsers();
        // })->fridays()->at('17:15');

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\TaskNotifier::notifyDaysLeft(3);
        // });

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\TaskNotifier::notifyDaysLeft(2);
        // });

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\TaskNotifier::notifyDaysLeft(1);
        // });
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
