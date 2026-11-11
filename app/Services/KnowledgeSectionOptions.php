<?php

namespace App\Services;

use App\Actions\GetTenantsForUpserts;
use App\Enums\KnowledgeSectionKind;
use App\Models\KnowledgeSection;
use App\Models\User;
use App\Services\Authorization\PermissionScope;

/**
 * What the Skyriai sheet may offer a user: the sections in their scope and the units they may
 * place one in. Sections belong to the Wiki, so its editors manage them; path builders keep the
 * access they had.
 */
class KnowledgeSectionOptions
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    /** @return array<string, array{canTargetEverywhere: bool, tenants: array<int, mixed>}> */
    public function scopes(User $user): array
    {
        $scopes = [];
        foreach (['create', 'update'] as $ability) {
            $scopes[$ability] = [
                'canTargetEverywhere' => $this->scope($user, $ability)->isAllScope,
                'tenants' => GetTenantsForUpserts::execute($this->permission($user, $ability), $this->authorizer)->all(),
            ];
        }

        return $scopes;
    }

    /** @return list<array{id: string, kind: KnowledgeSectionKind, date: string|null, title: array<string, string>, description: array<string, string>, order: int, tenant_id: int|null, itemsCount: int, canUpdate: bool, canDelete: bool, tenant: array{id: int, shortname: string}|null}> */
    public function options(User $user, string $ability, ?KnowledgeSection $only = null): array
    {
        $scope = $this->scope($user, $ability);
        $options = [];
        $sections = KnowledgeSection::query()->with('tenant')->withCount('items')
            ->when($only !== null, fn ($query) => $query->whereKey($only?->id))
            ->when($only === null && ! $scope->isAllScope, fn ($query) => $query->whereIn('tenant_id', $scope->tenantIds()))
            ->orderBy('order')->orderBy('id')->get();
        foreach ($sections as $section) {
            $options[] = [
                'id' => $section->id, 'kind' => $section->kind, 'date' => $section->date?->toDateString(),
                'title' => $section->getTranslations('title'), 'description' => $section->getTranslations('description'),
                'order' => $section->order, 'tenant_id' => $section->tenant_id, 'itemsCount' => (int) $section->items_count,
                'canUpdate' => $user->can('update', $section), 'canDelete' => $user->can('delete', $section),
                'tenant' => $section->tenant ? ['id' => $section->tenant->id, 'shortname' => (string) $section->tenant->shortname] : null,
            ];
        }

        return $options;
    }

    public function scope(User $user, string $ability): PermissionScope
    {
        return $this->authorizer->scope($user, $this->permission($user, $ability));
    }

    private function permission(User $user, string $ability): string
    {
        $knowledge = $this->authorizer->scope($user, "knowledgeItems.{$ability}.padalinys");
        $learning = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return ($knowledge->isAllScope || ($knowledge->granted && ! $learning->isAllScope) ? 'knowledgeItems' : 'learningSteps').".{$ability}.padalinys";
    }
}
