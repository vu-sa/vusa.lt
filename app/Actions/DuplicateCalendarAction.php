<?php

namespace App\Actions;

use App\Models\Calendar;
use Illuminate\Support\Facades\DB;

class DuplicateCalendarAction
{
    public static function execute(Calendar $calendar): Calendar
    {
        return DB::transaction(function () use ($calendar) {
            // Eager load relationships to avoid N+1 queries
            $calendar->load(['media']);

            // Replicate the calendar item
            $newCalendar = $calendar->replicate();
            
            // Handle translatable title field properly
            $rawTitle = $newCalendar->getTranslations('title');
            if (!empty($rawTitle)) {
                // For translatable fields, append to each language
                foreach ($rawTitle as $locale => $title) {
                    $suffix = $locale === 'lt' ? __('(kopija)', [], 'lt') : __('(kopija)', [], 'en');
                    $newCalendar->setTranslation('title', $locale, ($title ?? '') . ' ' . $suffix);
                }
            } else {
                // For simple string fields or when no translations exist
                $newCalendar->title = ($newCalendar->title ?? '') . ' ' . __('(kopija)');
            }
            
            $newCalendar->is_draft = true;
            $newCalendar->save();

            // Copy media files if they exist
            if ($calendar->media->isNotEmpty()) {
                foreach ($calendar->media as $media) {
                    $newCalendar
                        ->addMediaFromUrl($media->getFullUrl())
                        ->toMediaCollection($media->collection_name);
                }
            }

            return $newCalendar;
        });
    }
}
