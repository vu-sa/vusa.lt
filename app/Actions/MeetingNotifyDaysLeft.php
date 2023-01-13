<?php

namespace App\Actions;
use App\Models\Meeting;
use App\Notifications\MeetingSoonNotification;

class MeetingNotifyDaysLeft 
{
   public static function execute(int $daysLeft)
   {
    // get all institution meetings that their date and carbon now is equal or less, but not into future to $daysLeft
      $meetings = Meeting::with('institutions.users')->whereDate('start_time', '<=', now()->addDays($daysLeft))->whereDate('start_time', '>=', now())->get();
    // foreach institution meetings
    // send notification to institution members and institution managers
      foreach ($meetings as $meeting) {
         $meeting->users->each(function ($user) use ($meeting) {
            $user->notify(new MeetingSoonNotification($meeting));
         });
      }
   }
}
