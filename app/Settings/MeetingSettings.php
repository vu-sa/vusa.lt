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

    /**
     * Array of institution type IDs that don't hold formal meetings (e.g. padalinys, pkp), so
     * their health statistics would be meaningless.
     *
     * Scope note: this governs the Atstovavimas dashboard's *summaries* only. It no longer
     * removes anything from the Gantt — the chart draws every body and offers its own
     * "hide internal" toggle, keyed off the inherited governance scope rather than this
     * flat list, which misses child types.
     *
     * @var int[]
     */
    public array $excluded_institution_type_ids = [];

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
        $this->public_meeting_institution_type_ids = array_map(intval(...), array_filter($ids));
    }

    /**
     * Get excluded institution type IDs as Collection
     */
    public function getExcludedInstitutionTypeIds(): Collection
    {
        return collect($this->excluded_institution_type_ids)
            ->map(fn ($id) => (int) $id)
            ->filter();
    }

    /**
     * Set excluded institution type IDs from array
     */
    public function setExcludedInstitutionTypeIds(array $ids): void
    {
        $this->excluded_institution_type_ids = array_map(intval(...), array_filter($ids));
    }
}
