<?php

namespace Database\Factories;

use App\Enums\Responsibility;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DutyResponsibility>
 */
class DutyResponsibilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'duty_id' => Duty::factory(),
            'responsibility' => Responsibility::StudentRepCoordination,
            'scope_type' => ResponsibilityScope::Tenant->value,
            'scope_id' => fn () => (string) Tenant::query()->value('id'),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['scope_type' => ResponsibilityScope::Tenant->value, 'scope_id' => (string) $tenant->id]);
    }

    public function forType(InstitutionType $type): static
    {
        return $this->state(['scope_type' => ResponsibilityScope::InstitutionType->value, 'scope_id' => (string) $type->id]);
    }

    public function forInstitution(Institution $institution): static
    {
        return $this->state(['scope_type' => ResponsibilityScope::Institution->value, 'scope_id' => (string) $institution->id]);
    }
}
