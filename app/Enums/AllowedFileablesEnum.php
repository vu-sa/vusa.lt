<?php

namespace App\Enums;

use App\Contracts\SharepointFileableContract;
use App\Enums\Concerns\HasEnumHelpers;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Type;
use Illuminate\Database\Eloquent\Model;

/**
 * Models that may be addressed as a `fileable` in the SharePoint file endpoints.
 *
 * The backing values are the strings the frontend actually sends (`sharepoint/{type}/{id}`,
 * `<FileManager :fileable="{ type: 'Type' }">`), so this enum is the wire contract as well as
 * the allowlist. Resolving a model class from user input must always go through here.
 *
 * This replaces two separately-declared `const ALLOWED_FILEABLE_TYPES` arrays — one in
 * SharepointFileController, one in SharepointApiController. Being namespace-level constants in
 * different namespaces, they were two distinct values free to drift apart. The enum previously
 * also listed USER, which is not a SharepointFileableContract at all.
 */
enum AllowedFileablesEnum: string
{
    use HasEnumHelpers;

    case DUTY = 'Duty';
    case INSTITUTION = 'Institution';
    case MEETING = 'Meeting';
    case TYPE = 'Type';

    /**
     * @return class-string<Model&SharepointFileableContract>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::DUTY => Duty::class,
            self::INSTITUTION => Institution::class,
            self::MEETING => Meeting::class,
            self::TYPE => Type::class,
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
