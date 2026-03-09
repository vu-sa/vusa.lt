<?php

namespace App\Settings;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class FormSettings extends Settings
{
    public ?string $member_registration_form_id;

    public ?string $member_registration_notification_recipient_role_id;

    public ?string $student_rep_registration_form_id;

    /**
     * Institution type IDs that should show student representative registration.
     * Stored as JSON array.
     *
     * @var array<int>
     */
    public ?array $student_rep_institution_type_ids;

    public static function group(): string
    {
        return 'forms';
    }

    /**
     * Get institution type IDs for student representative registration as a Collection.
     */
    public function getStudentRepInstitutionTypeIds(): Collection
    {
        return collect($this->student_rep_institution_type_ids ?? []);
    }

    /**
     * Set institution type IDs for student representative registration.
     *
     * @param  array<int>  $typeIds
     */
    public function setStudentRepInstitutionTypeIds(array $typeIds): void
    {
        $this->student_rep_institution_type_ids = $typeIds;
    }
}
