<?php

namespace App\Actions;
use App\Models\InstitutionMeeting;

class InstitutionMeetingNotifyDaysLeft 
{
   public function execute(int $daysLeft)
   {
    // get all institution meetings that their date and carbon now is equal to $daysLeft
    // foreach institution meetings
    // send notification to institution members and institution managers
   }

}
