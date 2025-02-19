<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Matter;
use App\Models\Meeting;

class MeetingService
{
    /**
     * This function should take in a mattersForm, which is created in
     * NewMeetingButton.vue component.
     *
     * mattersForm is a bit of a magical array, which needs refactoring
     *
     * @param  Institution  $institution
     */
    public static function storeAndAttachMattersToMeeting(array $mattersForm, Meeting $meeting): void
    {
        $existingMatterIds = (array) $mattersForm['idArray'];
        $newMatterTitles = (array) $mattersForm['newTitleArray'];

        $meeting->matters()->attach($existingMatterIds);

        foreach ($newMatterTitles as $title) {
            $matter = Matter::create([
                'title' => $title,
                'description' => $mattersForm['newMatterDescription'],
                // 'status' => 'Sukurtas',
            ]);

            $matter->institutions()->attach($mattersForm['institution_id']);

            $meeting->matters()->attach($matter->id);
        }
    }
}
