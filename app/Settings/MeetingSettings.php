<?php

namespace App\Settings;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class MeetingSettings extends Settings
{
    /**
     * Array of institution type IDs that are allowed for public meeting display.
     * Stored as JSON in the database and automatically cast to/from array by the package.
     *
     * @var int[]
     */
    public array $public_meeting_institution_type_ids = [];

    public static function group(): string
    {
        return 'meetings';
    }

    /**
     * Get institution type IDs as Collection
     */
    public function getPublicMeetingInstitutionTypeIds(): Collection
    {
        return collect($this->public_meeting_institution_type_ids)
            ->map(fn ($id) => (int) $id)
            ->filter();
    }

    /**
     * Set institution type IDs from array
     */
    public function setPublicMeetingInstitutionTypeIds(array $ids): void
    {
        $this->public_meeting_institution_type_ids = array_map('intval', array_filter($ids));
    }
}
