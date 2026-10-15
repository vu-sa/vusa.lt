<?php

namespace App\Enums;

use App\Contracts\SharepointFileableContract;
use App\Enums\Concerns\HasEnumHelpers;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Model;

/**
 * Models that may be addressed as a `fileable` in the record file endpoints (`fileables/{type}/{id}/files`).
 * The backing values are what the frontend sends, so resolving a model class from user input must go through here.
 */
enum AllowedFileablesEnum: string
{
    use HasEnumHelpers;

    case DUTY = 'Duty';
    case INSTITUTION = 'Institution';
    case MEETING = 'Meeting';
    case INSTITUTION_TYPE = 'InstitutionType';
    case DUTY_TYPE = 'DutyType';

    /**
     * @return class-string<Model&SharepointFileableContract>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::DUTY => Duty::class,
            self::INSTITUTION => Institution::class,
            self::MEETING => Meeting::class,
            self::INSTITUTION_TYPE => InstitutionType::class,
            self::DUTY_TYPE => DutyType::class,
        };
    }

    /**
     * Resolve the model class for a request-supplied type, or null when it is not allowed.
     *
     * @return class-string<Model&SharepointFileableContract>|null
     */
    public static function classFor(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        return self::tryFrom($type)?->modelClass();
    }

    public function label(): string
    {
        return lcfirst($this->value);
    }
}
