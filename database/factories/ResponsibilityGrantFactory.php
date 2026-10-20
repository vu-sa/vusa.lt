<?php

namespace Database\Factories;

use App\Enums\ResponsibilityPurpose;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Defaults to the purpose-carrying coordination responsibility, granted to a new duty for a tenant.
 *
 * @extends Factory<ResponsibilityGrant>
 */
class ResponsibilityGrantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn () => RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination)->id,
            'grantee_type' => MorphMap::alias(Duty::class),
            'grantee_id' => fn () => Duty::factory()->create()->id,
            'scope_type' => ResponsibilityScope::Tenant->value,
            'scope_id' => fn () => (string) Tenant::query()->value('id'),
        ];
    }

    public function purpose(ResponsibilityPurpose $purpose): static
    {
        return $this->state(fn () => [
            'role_id' => RoleFactory::forPurpose($purpose)->id,
            ...($purpose->isScoped() ? [] : ['scope_type' => null, 'scope_id' => null]),
        ]);
    }

    public function of(Role $role): static
    {
        return $this->state(['role_id' => $role->id, 'scope_type' => null, 'scope_id' => null]);
    }

    public function forDuty(Duty $duty): static
    {
        return $this->state(['grantee_type' => MorphMap::alias(Duty::class), 'grantee_id' => $duty->id]);
    }

    public function forDutyType(DutyType $type): static
    {
        return $this->state(['grantee_type' => MorphMap::alias(DutyType::class), 'grantee_id' => (string) $type->id]);
    }

    public function forWholeInstitution(Institution $institution): static
    {
        return $this->state(['grantee_type' => MorphMap::alias(Institution::class), 'grantee_id' => $institution->id]);
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
