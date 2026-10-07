<?php

namespace App\Services;

use App\Enums\InstitutionRelationKind;
use App\Enums\TenantType;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The one place that turns institution and type links into "who is related to whom". The rules
 * and the invalidation contract are in `.ai/rules/services.md`.
 *
 * @phpstan-type Relation array{direction: 'outgoing'|'incoming'|'mutual', via: 'direct'|'type', kind: string, cross_tenant: bool, authorized: bool}
 * @phpstan-type RelatedInstitution array{institution_id: string, direction: 'outgoing'|'incoming'|'mutual', via: 'direct'|'type', kind: string, cross_tenant: bool, authorized: bool}
 */
class InstitutionRelationService
{
    public const VERSION_KEY = 'institution-relations-version';

    /** @var array<string, array<string, array<string, Relation>>> */
    private array $adjacency = [];

    public static function version(): string
    {
        return (string) Cache::get(self::VERSION_KEY, '');
    }

    public static function flush(): void
    {
        DB::afterCommit(fn () => Cache::forever(self::VERSION_KEY, (string) Str::uuid()));
    }

    /**
     * Everything related to one institution, from its point of view.
     *
     * @return Collection<int, RelatedInstitution>
     */
    public function relatedTo(string $institutionId): Collection
    {
        $related = [];
        foreach ($this->adjacency()[$institutionId] ?? [] as $id => $entry) {
            $related[] = ['institution_id' => (string) $id, ...$entry];
        }

        /** @var Collection<int, RelatedInstitution> $collection collect() would widen the literal types */
        $collection = collect($related);

        return $collection;
    }

    /**
     * Institutions the given institutions' members may see through relationships.
     *
     * @param  iterable<int, string>  $institutionIds
     * @return Collection<int, string>
     */
    public function authorizedIdsFor(iterable $institutionIds): Collection
    {
        $adjacency = $this->adjacency();

        return collect($institutionIds)
            ->flatMap(fn (string $id) => array_keys(array_filter($adjacency[$id] ?? [], fn (array $entry) => $entry['authorized'])))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    /**
     * The related-institutions panel payload: one row per related institution, with its name.
     *
     * @return list<array{id: string, name: mixed, direction: string, via: string, kind: string, kind_label: string, cross_tenant: bool, authorized: bool}>
     */
    public function summaryFor(Institution $institution): array
    {
        $related = $this->relatedTo($institution->id);
        $names = Institution::query()->whereIn('id', $related->pluck('institution_id'))->get(['id', 'name'])->keyBy('id');

        return $related
            ->filter(fn (array $entry) => $names->has($entry['institution_id']))
            ->map(fn (array $entry) => [
                'id' => $entry['institution_id'],
                'name' => $names[$entry['institution_id']]->name,
                'direction' => $entry['direction'],
                'via' => $entry['via'],
                'kind' => $entry['kind'],
                'kind_label' => InstitutionRelationKind::from($entry['kind'])->label(),
                'cross_tenant' => $entry['cross_tenant'],
                'authorized' => $entry['authorized'],
            ])
            ->values()
            ->all();
    }

    /**
     * The direct links an institution takes part in, from its side, for editing on its record.
     *
     * @return list<array{id: int, other: array{id: string, name: mixed}, direction: 'outgoing'|'incoming', kind: string, kind_label: string, mutual: bool}>
     */
    public function linksOf(Institution $institution): array
    {
        return InstitutionLink::query()
            ->where('source_institution_id', $institution->id)
            ->orWhere('target_institution_id', $institution->id)
            ->with(['source:id,name', 'target:id,name'])
            ->orderBy('id')
            ->get()
            ->map(function (InstitutionLink $link) use ($institution): ?array {
                $outgoing = $link->source_institution_id === $institution->id;
                $other = $outgoing ? $link->target : $link->source;

                return $other === null ? null : [
                    'id' => $link->id,
                    'other' => ['id' => $other->id, 'name' => $other->name],
                    'direction' => $outgoing ? 'outgoing' : 'incoming',
                    'kind' => $link->kind->value,
                    'kind_label' => $link->kind->label(),
                    'mutual' => $link->mutual,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The links a type takes part in, from its side. A self-link reads as outgoing.
     *
     * @return list<array{id: int, other: array{id: string, name: mixed}, direction: 'outgoing'|'incoming', kind: string, kind_label: string, mutual: bool, cross_tenant: bool}>
     */
    public function typeLinksOf(InstitutionType $type): array
    {
        return InstitutionTypeLink::query()
            ->where('source_type_id', $type->id)
            ->orWhere('target_type_id', $type->id)
            ->with(['source:id,title', 'target:id,title'])
            ->orderBy('id')
            ->get()
            ->map(function (InstitutionTypeLink $link) use ($type): ?array {
                $outgoing = $link->source_type_id === $type->id;
                $other = $outgoing ? $link->target : $link->source;

                return $other === null ? null : [
                    'id' => $link->id,
                    'other' => ['id' => (string) $other->id, 'name' => $other->title],
                    'direction' => $outgoing ? 'outgoing' : 'incoming',
                    'kind' => $link->kind->value,
                    'kind_label' => $link->kind->label(),
                    'mutual' => $link->mutual,
                    'cross_tenant' => $link->cross_tenant,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Related institutions of several institutions (the Atstovavimas dashboard), each carrying
     * which of the given institutions it came through. Authorized ones load meetings with agenda
     * items; the rest only what a restricted row shows.
     *
     * @param  EloquentCollection<int, Institution>  $institutions
     * @return EloquentCollection<int, Institution>
     */
    public function forMultiple(EloquentCollection $institutions): EloquentCollection
    {
        $ownIds = $institutions->modelKeys();
        $picked = [];

        foreach ($ownIds as $ownId) {
            foreach ($this->relatedTo((string) $ownId) as $entry) {
                $id = $entry['institution_id'];

                if (in_array($id, $ownIds, true) || (($picked[$id]['authorized'] ?? false) && ! $entry['authorized'])) {
                    continue;
                }

                if (! isset($picked[$id]) || $entry['authorized']) {
                    $picked[$id] = [...$entry, 'source_institution_id' => (string) $ownId];
                }
            }
        }

        if ($picked === []) {
            return new EloquentCollection;
        }

        $base = ['types', 'meetings:id,title,start_time,type', 'meetings.fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at', 'tenant:id,shortname', 'duties.users:id,name,profile_photo_path,last_action', 'duties.types:id,title,slug', 'checkIns'];
        $authorizedIds = array_keys(array_filter($picked, fn (array $entry) => $entry['authorized']));
        $restrictedIds = array_keys(array_filter($picked, fn (array $entry) => ! $entry['authorized']));

        $loaded = Institution::query()->whereIn('id', $authorizedIds)->with([
            ...$base,
            'meetings.agendaItems:id,meeting_id,title,type,brought_by_students,order,is_private,public_title',
            'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main',
        ])->get()->merge(Institution::query()->whereIn('id', $restrictedIds)->with($base)->get());

        return $loaded->each(function (Institution $institution) use ($picked): void {
            $entry = $picked[$institution->id];
            $institution->setAttribute('is_related', true);
            $institution->setAttribute('relationship_direction', $entry['direction']);
            $institution->setAttribute('relationship_type', $entry['via']);
            $institution->setAttribute('source_institution_id', $entry['source_institution_id']);
            $institution->setAttribute('authorized', $entry['authorized']);
        });
    }

    /**
     * Every institution-level edge, for the institution graph.
     *
     * @return list<array{source: string, target: string, via: string, kind: string, kind_label: string, cross_tenant: bool, mutual: bool}>
     */
    public function institutionGraph(): array
    {
        return array_map(fn (array $edge) => [
            'source' => $edge[0],
            'target' => $edge[1],
            'via' => $edge[2],
            'kind' => $edge[3],
            'kind_label' => InstitutionRelationKind::from($edge[3])->label(),
            'cross_tenant' => $edge[4],
            'mutual' => $edge[5],
        ], $this->edges());
    }

    /**
     * Every current type as a node (even unlinked ones) and the type links between them.
     *
     * @return array{nodes: list<array{id: string, name: mixed, institutions_count: int}>, edges: list<array{source: string, target: string, kind: string, kind_label: string, cross_tenant: bool, mutual: bool}>}
     */
    public function typeGraph(): array
    {
        return [
            'nodes' => InstitutionType::query()->withCount('institutions')->get()
                ->map(fn (InstitutionType $type) => ['id' => (string) $type->id, 'name' => $type->title, 'institutions_count' => (int) $type->institutions_count])
                ->values()->all(),
            'edges' => InstitutionTypeLink::query()->whereHas('source')->whereHas('target')->get()
                ->map(fn (InstitutionTypeLink $link) => [
                    'source' => (string) $link->source_type_id,
                    'target' => (string) $link->target_type_id,
                    'kind' => $link->kind->value,
                    'kind_label' => $link->kind->label(),
                    'cross_tenant' => $link->cross_tenant,
                    'mutual' => $link->mutual,
                ])->values()->all(),
        ];
    }

    /**
     * @return array<string, array<string, Relation>>
     */
    private function adjacency(): array
    {
        $version = self::version();

        if (isset($this->adjacency[$version])) {
            return $this->adjacency[$version];
        }

        $adjacency = [];
        $keep = function (string $viewer, string $other, array $entry) use (&$adjacency): void {
            $existing = $adjacency[$viewer][$other] ?? null;

            if ($existing === null
                || ($entry['authorized'] && ! $existing['authorized'])
                || ($entry['authorized'] === $existing['authorized'] && $entry['via'] === 'direct' && $existing['via'] !== 'direct')) {
                $adjacency[$viewer][$other] = $entry;
            }
        };

        foreach ($this->edges() as [$source, $target, $via, $kind, $crossTenant, $mutual]) {
            $shared = ['via' => $via, 'kind' => $kind, 'cross_tenant' => $crossTenant];
            $keep($source, $target, ['direction' => $mutual ? 'mutual' : 'outgoing', ...$shared, 'authorized' => true]);
            $keep($target, $source, ['direction' => $mutual ? 'mutual' : 'incoming', ...$shared, 'authorized' => $mutual]);
        }

        $this->adjacency = [$version => $adjacency];

        return $adjacency;
    }

    /**
     * Links expanded to institution pairs: [source, target, via, kind, cross_tenant, mutual].
     *
     * @return list<array{0: string, 1: string, 2: 'direct'|'type', 3: string, 4: bool, 5: bool}>
     */
    private function edges(): array
    {
        // Bounded TTL so superseded versions age out of the store.
        return Cache::remember('institution-relations:edges:'.self::version(), now()->addDay(), fn () => $this->buildEdges());
    }

    /**
     * @return list<array{0: string, 1: string, 2: 'direct'|'type', 3: string, 4: bool, 5: bool}>
     */
    private function buildEdges(): array
    {
        $edges = [];
        $add = function (string $source, string $target, string $via, string $kind, bool $crossTenant, bool $mutual) use (&$edges): void {
            if ($source === $target) {
                return;
            }

            // A same-type mutual link produces each pair twice, once per orientation.
            $key = $mutual && isset($edges["{$target}|{$source}"]) ? "{$target}|{$source}" : "{$source}|{$target}";
            $existing = $edges[$key] ?? null;

            if ($existing === null) {
                $edges[$key] = [$source, $target, $via, $kind, $crossTenant, $mutual];
            } elseif ($mutual && ! $existing[5]) {
                $edges[$key][5] = true;
            }
        };

        $assignments = DB::table('institution_institution_type as assignment')
            ->join('institutions', 'institutions.id', '=', 'assignment.institution_id')
            ->join('institution_types', 'institution_types.id', '=', 'assignment.institution_type_id')
            ->leftJoin('tenants', 'tenants.id', '=', 'institutions.tenant_id')
            ->whereNull('institutions.deleted_at')
            ->whereNull('institution_types.deleted_at')
            ->get(['assignment.institution_type_id as type_id', 'institutions.id', 'institutions.tenant_id', 'tenants.type as tenant_type']);

        $liveIds = Institution::query()->pluck('id')->flip();

        foreach (InstitutionLink::query()->orderBy('id')->get() as $link) {
            if ($liveIds->has($link->source_institution_id) && $liveIds->has($link->target_institution_id)) {
                $add($link->source_institution_id, $link->target_institution_id, 'direct', $link->kind->value, false, $link->mutual);
            }
        }

        $byType = $assignments->groupBy('type_id');

        foreach (InstitutionTypeLink::query()->orderBy('id')->get() as $link) {
            $sources = $byType->get($link->source_type_id, collect());
            $targets = $byType->get($link->target_type_id, collect());

            if ($link->cross_tenant) {
                $sources = $sources->where('tenant_type', TenantType::Pagrindinis->value);
                $targets = $targets->where('tenant_type', TenantType::Padalinys->value);
            }

            foreach ($sources as $source) {
                foreach ($targets as $target) {
                    if ($link->cross_tenant || $source->tenant_id === $target->tenant_id) {
                        $add((string) $source->id, (string) $target->id, 'type', $link->kind->value, $link->cross_tenant, $link->mutual);
                    }
                }
            }
        }

        return array_values($edges);
    }
}
