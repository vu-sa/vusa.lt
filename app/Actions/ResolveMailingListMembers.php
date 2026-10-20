<?php

namespace App\Actions;

use App\Enums\MailingListAddressMode;
use App\Enums\MailingListSourceType;
use App\Enums\MailingListTenantScope;
use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\MailingList;
use App\Models\MailingListSource;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use App\Support\TypeTree;
use App\ValueObjects\MailingListRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Assignment addresses take precedence when several sources reach the same person. */
class ResolveMailingListMembers
{
    private string $date;

    /** @var array<int, int|null>|null */
    private ?array $institutionTypeParents = null;

    /** @var array<int, int|null>|null */
    private ?array $dutyTypeParents = null;

    /**
     * Candidates keyed by user id, each carrying the address chosen so far.
     *
     * @var array<string, array{email: string, fromAssignment: bool, name: string, reasons: list<string>}>
     */
    private array $holders = [];

    /** @var array<string, MailingListRecipient> */
    private array $addresses = [];

    /**
     * @return Collection<int, MailingListRecipient>
     */
    public static function execute(MailingList $mailingList, ?Carbon $date = null): Collection
    {
        return (new self)->resolve($mailingList, ($date ?? Carbon::today())->toDateString());
    }

    /**
     * @return Collection<int, MailingListRecipient>
     */
    private function resolve(MailingList $mailingList, string $date, array $visitedListIds = []): Collection
    {
        $this->date = $date;

        $this->collectSources($mailingList, [...$visitedListIds, strtolower($mailingList->id)]);

        foreach ($this->holders as $userId => $holder) {
            $this->addAddress(new MailingListRecipient($holder['email'], $holder['name'], $userId, $holder['reasons'], $holder['fromAssignment']));
        }

        $excluded = $mailingList->exclusions()->pluck('email')->map(fn (string $email) => strtolower($email))->all();

        return collect($this->addresses)
            ->reject(fn (MailingListRecipient $recipient) => in_array($recipient->email, $excluded, true))
            ->sortBy('email')
            ->values();
    }

    /**
     * @param  list<string>  $visitedListIds  guards against a list that includes itself through others
     */
    private function collectSources(MailingList $mailingList, array $visitedListIds): void
    {
        foreach ($mailingList->sources()->get() as $source) {
            if ($source->isExpired($this->date)) {
                continue;
            }

            match ($source->type) {
                MailingListSourceType::Email => $this->collectEmail($source),
                MailingListSourceType::MailingList => $this->collectNestedList($source, $visitedListIds),
                default => $this->collectDuties($source),
            };
        }
    }

    private function collectEmail(MailingListSource $source): void
    {
        $email = $this->normalize($source->email);

        if ($email === null) {
            return;
        }

        $this->addAddress(new MailingListRecipient(
            $email,
            $source->display_name,
            null,
            [$source->note ? __('mailing_lists.reasons.manual_with_note', ['note' => $source->note]) : __('mailing_lists.reasons.manual')],
        ));
    }

    /**
     * @param  list<string>  $visitedListIds
     */
    private function collectNestedList(MailingListSource $source, array $visitedListIds): void
    {
        $sourceId = strtolower((string) $source->source_id);
        if ($sourceId === '' || in_array($sourceId, $visitedListIds, true)) {
            return;
        }

        $nested = MailingList::query()->find($sourceId);

        $tenantIds = $source->filter('authorized_tenant_ids');
        if ($nested === null || (is_array($tenantIds) && ! in_array($nested->tenant_id, $tenantIds, true))) {
            return;
        }

        $recipients = $nested->isManaged()
            ? (new self)->resolve($nested, $this->date, $visitedListIds)
            : $nested->members()->get()->map(fn ($member) => new MailingListRecipient(
                strtolower(trim($member->email)), $member->display_name, $member->user_id, [],
            ));

        foreach ($recipients as $recipient) {
            if ($recipient->userId === null) {
                $this->addAddress($recipient);

                continue;
            }

            $current = $this->holders[$recipient->userId] ?? null;
            $preferNested = $current === null || (! $current['fromAssignment'] && $recipient->assignmentAddress);
            $this->holders[$recipient->userId] = [
                'email' => $preferNested ? $recipient->email : $current['email'],
                'name' => $preferNested ? $recipient->displayName : $current['name'],
                'fromAssignment' => $preferNested ? $recipient->assignmentAddress : $current['fromAssignment'],
                'reasons' => array_values(array_unique([...($current['reasons'] ?? []), ...$recipient->reasons])),
            ];
        }
    }

    private function collectDuties(MailingListSource $source): void
    {
        $duties = $this->dutiesFor($source)
            ->where('is_active', true)
            ->whereHas('institution', function (Builder $query) use ($source): void {
                $query->where('is_active', true);
                $tenantIds = $source->filter('authorized_tenant_ids');
                if (is_array($tenantIds)) {
                    $query->whereIn('tenant_id', $tenantIds);
                }
            })
            ->with('institution:id,name')
            ->get();

        if ($source->address_mode === MailingListAddressMode::DutyMailbox) {
            $withMailbox = $duties->filter(fn (Duty $duty) => $this->normalize($duty->email) !== null);

            foreach ($withMailbox as $duty) {
                $this->addAddress(new MailingListRecipient(
                    $this->normalize($duty->email),
                    $duty->name,
                    null,
                    [__('mailing_lists.reasons.duty_mailbox', ['duty' => $this->dutyLabel($duty)])],
                ));
            }

            $duties = $duties->diff($withMailbox);
        }

        $this->collectHolders($duties);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Duty>  $duties
     */
    private function collectHolders(\Illuminate\Database\Eloquent\Collection $duties): void
    {
        if ($duties->isEmpty()) {
            return;
        }

        $dutiesById = $duties->keyBy('id');

        $assignments = Dutiable::query()
            ->activeOn($this->date)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereIn('duty_id', $dutiesById->keys())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $users = User::query()->whereIn('id', $assignments->pluck('dutiable_id')->unique())->get()->keyBy('id');

        foreach ($assignments as $assignment) {
            $user = $users->get($assignment->dutiable_id);

            if ($user !== null) {
                $this->addHolder($user, $assignment, $dutiesById->get($assignment->duty_id));
            }
        }
    }

    private function addHolder(User $user, Dutiable $assignment, Duty $duty): void
    {
        $assignmentEmail = $this->normalize($assignment->additional_email);
        $email = $assignmentEmail ?? $this->normalize($user->email);

        if ($email === null) {
            return;
        }

        $reason = $this->dutyLabel($duty);
        $current = $this->holders[$user->id] ?? null;

        if ($current === null) {
            $this->holders[$user->id] = [
                'email' => $email,
                'fromAssignment' => $assignmentEmail !== null,
                'name' => $user->name,
                'reasons' => [$reason],
            ];

            return;
        }

        if (! $current['fromAssignment'] && $assignmentEmail !== null) {
            $current['email'] = $assignmentEmail;
            $current['fromAssignment'] = true;
        }

        $current['reasons'] = array_values(array_unique([...$current['reasons'], $reason]));
        $this->holders[$user->id] = $current;
    }

    private function addAddress(MailingListRecipient $recipient): void
    {
        $existing = $this->addresses[$recipient->email] ?? null;

        $this->addresses[$recipient->email] = $existing?->withReasons($recipient->reasons) ?? $recipient;
    }

    /**
     * @return Builder<Duty>
     */
    private function dutiesFor(MailingListSource $source): Builder
    {
        $query = Duty::query();

        return match ($source->type) {
            MailingListSourceType::Duty => $query->whereKey($source->source_id),
            MailingListSourceType::DutyType => $this->scopeToTenants(
                $query->whereHas('types', fn (Builder $q) => $q->whereIn('duty_types.id', $this->dutyTypeIds($source, [(int) $source->source_id]))),
                $source,
            ),
            MailingListSourceType::Institution => $this->filterDutyTypes(
                $query->where('institution_id', $source->source_id),
                $source,
            ),
            MailingListSourceType::Responsibility => $this->scopeToTenants(
                $query->whereHas('roles', fn (Builder $roles) => $roles->whereKey($source->source_id)),
                $source,
            ),
            MailingListSourceType::InstitutionType => $this->filterDutyTypes(
                $query->whereIn('institution_id', $this->institutionIdsForType($source)),
                $source,
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @return Collection<int, string>
     */
    private function institutionIdsForType(MailingListSource $source): Collection
    {
        if (! array_key_exists((int) $source->source_id, $this->institutionTypeParents())) {
            return collect();
        }

        $includeSubtypes = (bool) $source->filter('include_subtypes', true);
        $typeIds = [(int) $source->source_id];
        $typeIds = $includeSubtypes ? TypeTree::withDescendants($this->institutionTypeParents(), $typeIds) : $typeIds;

        $excludedTypeIds = array_map(intval(...), (array) $source->filter('exclude_institution_type_ids', []));
        $excludedTypeIds = $excludedTypeIds === [] ? [] : TypeTree::withDescendants($this->institutionTypeParents(), $excludedTypeIds);

        $query = Institution::query()
            ->where('is_active', true)
            ->whereHas('types', fn (Builder $q) => $q->whereIn('institution_types.id', $typeIds));

        if ($excludedTypeIds !== []) {
            $query->whereDoesntHave('types', fn (Builder $q) => $q->whereIn('institution_types.id', $excludedTypeIds));
        }

        $tenantIds = $this->tenantIds($source);

        if ($tenantIds !== null) {
            $query->whereIn('tenant_id', $tenantIds);
        }

        return $query->pluck('id');
    }

    /**
     * @param  Builder<Duty>  $query
     * @return Builder<Duty>
     */
    private function filterDutyTypes(Builder $query, MailingListSource $source): Builder
    {
        $dutyTypeIds = array_map(intval(...), (array) $source->filter('duty_type_ids', []));

        if ($dutyTypeIds === []) {
            return $query;
        }

        return $query->whereHas('types', fn (Builder $q) => $q->whereIn('duty_types.id', $this->dutyTypeIds($source, $dutyTypeIds)));
    }

    /**
     * @param  Builder<Duty>  $query
     * @return Builder<Duty>
     */
    private function scopeToTenants(Builder $query, MailingListSource $source): Builder
    {
        $tenantIds = $this->tenantIds($source);

        if ($tenantIds === null) {
            return $query;
        }

        return $query->whereHas('institution', fn (Builder $q) => $q->where('is_active', true)->whereIn('tenant_id', $tenantIds));
    }

    /**
     * @param  list<int>  $typeIds
     * @return list<int>
     */
    private function dutyTypeIds(MailingListSource $source, array $typeIds): array
    {
        $this->dutyTypeParents ??= $this->parentsOf(DutyType::query());
        $typeIds = array_values(array_filter($typeIds, fn (int $id) => array_key_exists($id, $this->dutyTypeParents)));
        if (! (bool) $source->filter('include_subtypes', true)) {
            return $typeIds;
        }

        return TypeTree::withDescendants($this->dutyTypeParents, $typeIds);
    }

    /**
     * Null means every tenant.
     *
     * @return list<int>|null
     */
    private function tenantIds(MailingListSource $source): ?array
    {
        $scope = MailingListTenantScope::tryFrom((string) $source->filter('tenant_scope', 'all')) ?? MailingListTenantScope::All;

        return match ($scope) {
            MailingListTenantScope::All => null,
            MailingListTenantScope::Main => array_filter([Tenant::main()?->id]),
            MailingListTenantScope::Padaliniai => Tenant::query()->where('type', TenantType::Padalinys)->pluck('id')->all(),
            MailingListTenantScope::Selected => array_map(intval(...), (array) $source->filter('tenant_ids', [])),
        };
    }

    /**
     * @return array<int, int|null>
     */
    private function institutionTypeParents(): array
    {
        return $this->institutionTypeParents ??= $this->parentsOf(InstitutionType::query());
    }

    /**
     * @param  Builder<DutyType>|Builder<InstitutionType>  $query
     * @return array<int, int|null>
     */
    private function parentsOf(Builder $query): array
    {
        return $query->pluck('parent_id', 'id')
            ->mapWithKeys(fn ($parent, $id) => [(int) $id => $parent === null ? null : (int) $parent])
            ->all();
    }

    private function dutyLabel(Duty $duty): string
    {
        $institution = $duty->institution?->name;

        return $institution ? "{$institution} · {$duty->name}" : (string) $duty->name;
    }

    private function normalize(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
