<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Which tenants a type link pairs institutions across.
 */
enum InstitutionTypeLinkScope: string
{
    use HasEnumHelpers;

    case SameTenant = 'same_tenant';
    case MainToUnits = 'main_to_units';
    case AllTenants = 'all_tenants';

    public function label(): string
    {
        return match ($this) {
            self::SameTenant => __('Padalinio viduje'),
            self::MainToUnits => __('Centrinis → padaliniai'),
            self::AllTenants => __('Tarp visų padalinių'),
        };
    }

    public function crossesTenants(): bool
    {
        return $this !== self::SameTenant;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $scope) => ['value' => $scope->value, 'label' => $scope->label()], self::cases());
    }
}
