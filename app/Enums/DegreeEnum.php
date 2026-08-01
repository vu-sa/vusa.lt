<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum DegreeEnum: string
{
    use HasEnumHelpers;

    case BA = 'BA';
    case MA = 'MA';
    case PHD = 'PHD';
    case INTEGRATED_STUDIES = 'INTEGRATED_STUDIES';
    case PROFESSIONAL_PEDAGOGY = 'PROFESSIONAL_PEDAGOGY';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::BA => 'BA',
            self::MA => 'MA',
            self::PHD => 'PhD',
            self::INTEGRATED_STUDIES => 'Integrated Studies',
            self::PROFESSIONAL_PEDAGOGY => 'Professional Pedagogy',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get options for form select components with human-readable labels.
     */
    public static function getFormOptions(): array
    {
        return [
            ['label' => 'Bakalauras (BA)', 'value' => self::BA->value],
            ['label' => 'Magistras (MA)', 'value' => self::MA->value],
            ['label' => 'Daktaras (PhD)', 'value' => self::PHD->value],
            ['label' => 'Vientisosiosios studijos (Integrated Studies)', 'value' => self::INTEGRATED_STUDIES->value],
            ['label' => 'Profesinės pedagogikos studijos (Professional Pedagogy)', 'value' => self::PROFESSIONAL_PEDAGOGY->value],
            ['label' => 'Kita (Other)', 'value' => self::OTHER->value],
        ];
    }

    /**
     * Get all degree values for validation rules.
     */
    public static function getValidationRule(): string
    {
        return 'in:'.implode(',', self::values());
    }
}
