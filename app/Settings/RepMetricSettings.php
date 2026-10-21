<?php

namespace App\Settings;

use App\Enums\InstitutionScope;
use Spatie\LaravelSettings\Settings;

class RepMetricSettings extends Settings
{
    public ?string $baseline_cadence_id = null;

    /** @var list<string> InstitutionScope values whose institutions the representation metrics count */
    public array $counted_institution_scopes = [InstitutionScope::University->value];

    public static function group(): string
    {
        return 'rep_metrics';
    }

    /**
     * @return list<InstitutionScope>
     */
    public function countedInstitutionScopes(): array
    {
        return array_values(array_filter(array_map(InstitutionScope::tryFrom(...), $this->counted_institution_scopes)));
    }
}
