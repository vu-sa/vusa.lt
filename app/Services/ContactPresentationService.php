<?php

namespace App\Services;

use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ContactPresentationService
{
    /**
     * One card per person, in duty order. `contacts_grouping` orders a duty's holders and, for
     * `tenant`, labels each card with the represented tenant — it never splits the page.
     *
     * @param  Collection<int, Duty>  $duties  with `current_users` loaded
     * @return list<array<string, mixed>>
     */
    public function contactsFor(Institution $institution, Collection $duties): array
    {
        $institution->loadMissing('tenant');
        $duties->each(fn (Duty $duty) => $duty->setRelation('institution', $institution));

        new EloquentCollection($duties->flatMap(fn (Duty $duty) => $duty->current_users->pluck('pivot'))->all())
            ->load(['tenant:id,shortname', 'study_program:id,name', 'media']);

        new EloquentCollection($duties->flatMap(fn (Duty $duty) => $duty->current_users)->all())->load('media');

        $contacts = [];

        foreach ($this->orderDuties($institution, $duties) as $duty) {
            foreach ($this->orderHolders($duty) as $user) {
                $contacts[$user->id] ??= $this->contact($user);

                // A second term in the same duty would repeat the line on the card.
                if (collect($contacts[$user->id]['duties'])->contains('id', $duty->id)) {
                    continue;
                }

                $contacts[$user->id]['duties'][] = $this->dutyEntry($duty, $user->pivot);
            }
        }

        return array_values($contacts);
    }

    /**
     * On a padalinys, leaders and coordinators come first, then curators, then everyone else.
     *
     * @param  Collection<int, Duty>  $duties
     * @return Collection<int, Duty>
     */
    private function orderDuties(Institution $institution, Collection $duties): Collection
    {
        if ($institution->tenant?->type !== TenantType::Padalinys) {
            return $duties->sortBy('order')->values();
        }

        $rootTypes = DutyType::query()->whereIn('slug', ['koordinatoriai', 'kuratoriai', 'mentors'])->get();
        $coordinatorRoot = $rootTypes->firstWhere('slug', 'koordinatoriai');
        $coordinatorIds = $coordinatorRoot?->getDescendantsAndSelf()->pluck('id') ?? collect();
        $curatorIds = $rootTypes->whereIn('slug', ['kuratoriai', 'mentors'])
            ->flatMap(fn (DutyType $type) => $type->getDescendantsAndSelf()->pluck('id'));

        $rank = function (Duty $duty) use ($coordinatorRoot, $coordinatorIds, $curatorIds): int {
            $typeIds = $duty->types->pluck('id');

            return match (true) {
                $typeIds->intersect($coordinatorIds)->diff([$coordinatorRoot?->id])->isNotEmpty() => 0,
                $typeIds->intersect($coordinatorIds)->isNotEmpty() => 1,
                $typeIds->intersect($curatorIds)->isNotEmpty() => 2,
                default => 3,
            };
        };

        return $duties->sortBy([
            fn (Duty $a, Duty $b) => $rank($a) <=> $rank($b),
            fn (Duty $a, Duty $b) => $a->order <=> $b->order,
        ])->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function orderHolders(Duty $duty): Collection
    {
        if (in_array($duty->contacts_grouping, [null, 'none'], true)) {
            return $duty->current_users;
        }

        $fallback = $this->fallbackGroupName();

        return $duty->current_users->sortBy([
            fn (User $a, User $b) => $this->groupSortKey($a, $duty, $fallback) <=> $this->groupSortKey($b, $duty, $fallback),
            fn (User $a, User $b) => strnatcasecmp((string) $a->name, (string) $b->name),
        ])->values();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function groupSortKey(User $user, Duty $duty, string $fallback): array
    {
        $key = $this->getGroupKey($user->pivot, $duty->contacts_grouping, $duty);

        return [$key === $fallback, mb_strtolower($key)];
    }

    /**
     * @return array<string, mixed>
     */
    private function contact(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'facebook_url' => $user->facebook_url,
            'duties' => [],
            'profile_photo_path' => $user->profile_photo_path,
            'profile_photo_media' => $user->imageData('profile_photo'),
            'profile_photo_focal_point' => $user->profile_photo_focal_point,
            'pronouns' => $user->pronouns,
            'duty_pronouns' => $user->getTranslations('pronouns'),
            'show_pronouns' => $user->show_pronouns,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dutyEntry(Duty $duty, Dutiable $dutiable): array
    {
        return [
            'id' => $duty->id,
            'name' => $duty->name,
            'description' => $duty->description,
            'email' => $duty->email,
            'contact_tenant' => $duty->contacts_grouping === 'tenant' ? $this->tenantLabel($dutiable, $duty) : null,
            'pivot' => [
                'use_original_duty_name' => (bool) $dutiable->use_original_duty_name,
                'additional_email' => $dutiable->additional_email,
                'additional_photo' => $dutiable->additional_photo,
                'photo_media' => $dutiable->imageData('photo'),
                'additional_photo_focal_point' => $dutiable->additional_photo_focal_point,
                'description' => $dutiable->description,
                'study_program' => $dutiable->study_program?->only(['id', 'name']),
                'study_program_note' => $dutiable->study_program_note,
            ],
        ];
    }

    private function tenantLabel(Dutiable $dutiable, Duty $duty): ?string
    {
        $label = $this->getGroupKey($dutiable, 'tenant', $duty);

        return $label === $this->fallbackGroupName() ? null : $label;
    }

    /**
     * Get the group key based on grouping type.
     */
    public function getGroupKey(Dutiable $dutiable, string $groupingType, ?Duty $duty = null): string
    {
        return match ($groupingType) {
            'study_program' => $dutiable->study_program
                ? $dutiable->study_program->name
                : $this->fallbackGroupName(),
            // The padalinys the member represents: the cross-tenant assignment
            // (dutiables.tenant_id), or the duty's own tenant when null.
            'tenant' => $dutiable->tenant?->shortname // @phpstan-ignore nullsafe.neverNull
                ?? $duty?->loadMissing('institution.tenant')->institution?->tenant?->shortname // @phpstan-ignore nullsafe.neverNull
                ?? $this->fallbackGroupName(),
            default => $this->fallbackGroupName(),
        };
    }

    /**
     * Name of the group for contacts that cannot be grouped (translated).
     */
    private function fallbackGroupName(): string
    {
        return __('Kita');
    }
}
