<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Enums\Traits\HasCamelCaseLabels;
use App\Models\Institution;
use App\Models\Type;
use Illuminate\Database\Eloquent\Model;

/**
 * Models that may take part in a relationship.
 *
 * This is the single source of truth for that allowlist. It previously had two other
 * spellings: RelationshipService::allowedModelClasses() (which now derives from here) and a
 * hand-built list in resources/js/Types/formOptions.ts.
 */
enum AllowedRelationshipablesEnum: string
{
    use HasCamelCaseLabels;
    use HasEnumHelpers;

    case INSTITUTION = 'INSTITUTION';
    case TYPE = 'TYPE';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::INSTITUTION => Institution::class,
            self::TYPE => Type::class,
        };
    }

    /**
     * @return list<class-string<Model>>
     */
    public static function modelClasses(): array
    {
        return array_map(fn (self $case) => $case->modelClass(), self::cases());
    }
}
