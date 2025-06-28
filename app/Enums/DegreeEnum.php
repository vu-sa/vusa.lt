<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self BA()
 * @method static self MA()
 * @method static self PHD()
 * @method static self INTEGRATED_STUDIES()
 * @method static self PROFESSIONAL_PEDAGOGY()
 * @method static self OTHER()
 */
final class DegreeEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'BA' => 'BA',
            'MA' => 'MA',
            'PHD' => 'PhD',
            'INTEGRATED_STUDIES' => 'Integrated Studies',
            'PROFESSIONAL_PEDAGOGY' => 'Professional Pedagogy',
            'OTHER' => 'Other',
        ];
    }

    /**
     * Get options for form select components with human-readable labels
     */
    public static function getFormOptions(): array
    {
        return [
            ['label' => 'Bakalauras (BA)', 'value' => self::BA()->value],
            ['label' => 'Magistras (MA)', 'value' => self::MA()->value],
            ['label' => 'Daktaras (PhD)', 'value' => self::PHD()->value],
            ['label' => 'Vientisosiosios studijos (Integrated Studies)', 'value' => self::INTEGRATED_STUDIES()->value],
            ['label' => 'Profesinės pedagogikos studijos (Professional Pedagogy)', 'value' => self::PROFESSIONAL_PEDAGOGY()->value],
            ['label' => 'Kita (Other)', 'value' => self::OTHER()->value],
        ];
    }

    /**
     * Get all degree values for validation rules
     */
    public static function getValidationRule(): string
    {
        return 'in:' . implode(',', self::toValues());
    }
}
