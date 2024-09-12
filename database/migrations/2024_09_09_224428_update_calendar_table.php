<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->renameColumn('title', 'title_old');
            $table->renameColumn('description', 'description_old');
            $table->renameColumn('location', 'location_old');
            $table->renameColumn('url', 'url_old');

            $table->json('title')->nullable()->after('id');
            $table->json('description')->nullable()->after('title');
            $table->json('location')->nullable()->after('description');
            $table->json('organizer')->nullable()->after('location');
            $table->json('cto_url')->nullable()->after('organizer')->comment('URL for Call To Action');
            $table->text('facebook_url')->nullable()->after('cto_url');
            $table->string('video_url')->nullable()->after('facebook_url');
            $table->boolean('is_draft')->default(false)->after('video_url');
            $table->boolean('is_all_day')->default(false)->after('is_draft');
            $table->boolean('is_international')->default(false)->after('is_all_day');
        });

        $calendar = \App\Models\Calendar::all();

        foreach ($calendar as $calendarEvent) {

            $calendarEvent->setTranslation('title', 'lt', $calendarEvent->title_old);

            $extra_attributes = json_decode($calendarEvent->extra_attributes);

            if (isset($extra_attributes?->en?->title)) {
                $calendarEvent->setTranslation('title', 'en', $extra_attributes->en->title);
            }

            if (isset($calendarEvent->description_old)) {
                $calendarEvent->setTranslation('description', 'lt', $calendarEvent->description_old);
            }

            if (isset($extra_attributes?->en?->description)) {
                $calendarEvent->setTranslation('description', 'en', $extra_attributes->en->description);
            }

            if (isset($calendarEvent->location_old)) {
                $calendarEvent->setTranslation('location', 'lt', $calendarEvent->location_old);
            }

            if (isset($extra_attributes?->en?->location)) {
                $calendarEvent->setTranslation('location', 'en', $extra_attributes->en->location);
            }

            if (isset($extra_attributes?->organizer)) {
                $calendarEvent->setTranslation('organizer', 'lt', $extra_attributes->organizer);
            }

            if (isset($extra_attributes?->all_day)) {
                $calendarEvent->is_all_day = $extra_attributes->all_day;
            }

            if (isset($extra_attributes?->en?->organizer)) {
                $calendarEvent->setTranslation('organizer', 'en', $extra_attributes->en->organizer);
            }

            if (isset($calendarEvent->url_old)) {
                $calendarEvent->setTranslation('cto_url', 'lt', $calendarEvent->url_old);
            }

            if (isset($extra_attributes?->en?->url)) {
                $calendarEvent->setTranslation('cto_url', 'en', $extra_attributes->en->url);
            }

            if (isset($extra_attributes?->en?->shown)) {
                $calendarEvent->is_international = $extra_attributes->en->shown;
            }

            if (isset($extra_attributes?->facebook_url)) {
                // check facebook_url length
                $calendarEvent->facebook_url = $extra_attributes->facebook_url;
            }

            if (isset($extra_attributes?->video_url)) {
                $calendarEvent->video_url = $extra_attributes->video_url;
            }

            $calendarEvent->save();
        }

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('title_old');
            $table->dropColumn('description_old');
            $table->dropColumn('location_old');
            $table->dropColumn('url_old');
            $table->dropColumn('extra_attributes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
