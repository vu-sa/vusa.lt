<?php

namespace App\Actions\Schedulable;

use App\Models\Meeting;
use App\Notifications\MeetingNotFinishedNotification;
use App\Notifications\MeetingSoonNotification;

class MeetingNotifier 
{
   public static function notifyDaysLeft(int $daysLeft)
   {
    // get all institution meetings that their date and carbon now is equal or less, but not into future to $daysLeft
      $meetings = Meeting::with('institutions.users')->whereDate('start_time', '<=', now()->addDays($daysLeft))->whereDate('start_time', '>=', now())->get();
      
      foreach ($meetings as $meeting) {
         $meeting->users->each(function ($user) use ($meeting) {
            $user->notify(new MeetingSoonNotification($meeting));
         });
      }
   }

   // Used for inform, that the meeting is not finished, i.e. the files were not uploaded
   public static function notifyOnMeetingUnfinishedStatus()
   {
      $meetings = Meeting::with('institutions.users')->whereDate('start_time', '<=', now())->where('status', '!=', 'Pabaigtas')->get();
      
      foreach ($meetings as $meeting) {
         $meeting->users->each(function ($user) use ($meeting) {
            $user->notify(new MeetingNotFinishedNotification($meeting));
         });
      }
   }
}
