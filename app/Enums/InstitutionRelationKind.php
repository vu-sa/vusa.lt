<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * What a link between two institutions (or institution types) means. Purely descriptive:
 * access follows direction and `mutual`, never the kind.
 */
enum InstitutionRelationKind: string
{
    use HasEnumHelpers;

    case Advisory = 'advisory';
    case ApprovesComposition = 'approves_composition';
    case ForwardsIssues = 'forwards_issues';
    case Oversees = 'oversees';
    case Cooperates = 'cooperates';
    case Related = 'related';

    public function label(): string
    {
        return match ($this) {
            self::Advisory => __('Patariamasis'),
            self::ApprovesComposition => __('Tvirtina sudėtį'),
            self::ForwardsIssues => __('Klausimai keliauja toliau'),
            self::Oversees => __('Kuruoja'),
            self::Cooperates => __('Bendradarbiauja'),
            self::Related => __('Susijusi'),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $kind) => ['value' => $kind->value, 'label' => $kind->label()], self::cases());
    }
}
