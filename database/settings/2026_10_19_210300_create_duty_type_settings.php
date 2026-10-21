<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Pre-filled from the slugs the contact pages looked up until now, so nothing moves on deploy.
 */
return new class extends SettingsMigration
{
    private const array SLUGS = [
        'coordinator_type_id' => 'koordinatoriai',
        'curator_type_id' => 'kuratoriai',
        'mentor_type_id' => 'mentors',
    ];

    public function up(): void
    {
        foreach (self::SLUGS as $key => $slug) {
            $this->migrator->add("duty_types.{$key}", DB::table('duty_types')->where('slug', $slug)->whereNull('deleted_at')->value('id'));
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::SLUGS) as $key) {
            $this->migrator->deleteIfExists("duty_types.{$key}");
        }
    }
};
