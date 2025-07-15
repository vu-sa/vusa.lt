<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReminderToLoginNotification;
use Illuminate\Console\Command;

class SendRemindersToLoginCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vusa:send-reminders-to-reps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends reminders to login to student reps who have not logged in last 3 months or not at all.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Don't send to old student reps
        // 2. Don't send to Parliament and other internal.
        $studentReps = User::query()
            ->withWhereHas('current_duties', function ($query) {
                $query->withWhereHas('institution',
                    function ($query) {
                        $query->where('is_active', true);
                    }
                )->where('name->lt', '!=', 'Parlamento narys')->where('name->lt', '!=', 'Tarybos narys')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Studentų atstovas');
                    });
            })
            ->where(function ($query) {
                $query->where('last_action', null)
                    ->orWhere('last_action', '<', now()->subMonths(3));
            }
            )->get();

        // Check if valid emails
        $studentReps = $studentReps->filter(function ($user) {
            return filter_var($user->email, FILTER_VALIDATE_EMAIL);
        })->values();

        // Send a reminder to each student rep
        foreach ($studentReps as $studentRep) {
            $studentRep->notify(new ReminderToLoginNotification);
        }
    }
}
