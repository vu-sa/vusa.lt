<?php

use App\Models\Calendar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfill main_image from first media item for existing calendar events.
     * Also copies the URL-based main_image to the new Spatie media collection.
     */
    public function up(): void
    {
        // First, handle calendars with URL-based main_image - migrate to Spatie
        Calendar::whereNotNull('main_image')
            ->whereDoesntHave('media', function ($query) {
                $query->where('collection_name', 'main_image');
            })
            ->each(function (Calendar $calendar) {
                try {
                    $calendar->addMediaFromUrl($calendar->main_image)
                        ->toMediaCollection('main_image');
                } catch (\Exception $e) {
                    // Log the error but continue with other records
                    \Illuminate\Support\Facades\Log::warning(
                        "Failed to migrate main_image for calendar {$calendar->id}: {$e->getMessage()}"
                    );
                }
            });

        // Then, for calendars without any main_image, use first gallery image
        Calendar::whereNull('main_image')
            ->whereDoesntHave('media', function ($query) {
                $query->where('collection_name', 'main_image');
            })
            ->with('media')
            ->each(function (Calendar $calendar) {
                $firstMedia = $calendar->getFirstMedia('images');
                if ($firstMedia) {
                    try {
                        // Copy the first gallery image to main_image collection
                        $calendar->addMediaFromUrl($firstMedia->getUrl())
                            ->toMediaCollection('main_image');
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning(
                            "Failed to set main_image from gallery for calendar {$calendar->id}: {$e->getMessage()}"
                        );
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all main_image media collection items
        Calendar::each(function (Calendar $calendar) {
            $calendar->clearMediaCollection('main_image');
        });
    }
};
