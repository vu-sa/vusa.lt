<?php

namespace App\Services;

use App\Enums\FormOptionSource;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Tenant;
use App\Settings\FormSettings;
use Illuminate\Support\Collection;

class FormOptionResolver
{
    public function __construct(private readonly FormSettings $formSettings) {}

    /**
     * @return Collection<int, array{value: int|string, label: string}>
     */
    public function optionsFor(Form $form, FormField $field, ?string $preselectedInstitutionId = null): Collection
    {
        return match ($field->resolvedOptionSource()) {
            FormOptionSource::Tenant => Tenant::query()
                ->orderBy('fullname')
                ->get(['id', 'fullname'])
                ->map(fn (Tenant $tenant) => $this->optionForTenant($tenant)),
            FormOptionSource::Institution => $this->institutionsFor($form, $preselectedInstitutionId)
                ->map(fn (Institution $institution) => $this->optionForInstitution($institution)),
            default => collect(),
        };
    }

    /** @return array{value: int|string, label: string} */
    private function optionForTenant(Tenant $tenant): array
    {
        return [
            'value' => $tenant->id,
            'label' => $tenant->fullname,
        ];
    }

    /** @return array{value: int|string, label: string} */
    private function optionForInstitution(Institution $institution): array
    {
        $name = $institution->name;

        return [
            'value' => $institution->id,
            'label' => is_string($name) ? $name : '',
        ];
    }

    public function accepts(FormField $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return match ($field->resolvedOptionSource()) {
            FormOptionSource::Tenant => Tenant::query()->whereKey($value)->exists(),
            FormOptionSource::Institution => Institution::query()->whereKey($value)->exists(),
            default => false,
        };
    }

    /** @return Collection<int, Institution> */
    private function institutionsFor(Form $form, ?string $preselectedInstitutionId): Collection
    {
        if ($form->id !== $this->formSettings->student_rep_registration_form_id) {
            return Institution::query()->orderBy('name')->get(['id', 'name']);
        }

        $allowedTypeIds = $this->formSettings->getStudentRepInstitutionTypeIds();

        $query = Institution::query()->where(function ($query) use ($preselectedInstitutionId): void {
            $query->whereDoesntHave('duties.current_users');

            if ($preselectedInstitutionId !== null) {
                $query->orWhereKey($preselectedInstitutionId);
            }
        });

        if ($allowedTypeIds->isNotEmpty()) {
            $query->whereHas('types', fn ($typeQuery) => $typeQuery->whereIn('types.id', $allowedTypeIds));
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }
}
