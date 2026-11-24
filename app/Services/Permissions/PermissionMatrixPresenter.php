<?php

namespace App\Services\Permissions;

use App\Enums\ModelEnum;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use App\Support\Permissions\BaselineAccess;
use App\Support\Permissions\PermissionCatalog;

/**
 * Presentation metadata for the responsibility permission matrix editor: workspace grouping,
 * row ordering and translated scope copy, built on top of {@see PermissionCatalog}.
 */
final class PermissionMatrixPresenter
{
    /** Resource => workspace. Listed in admin navigation order; the editor sorts rows by it. */
    public const array RESOURCE_AREAS = [
        'tasks' => 'pradzia',
        'comments' => 'pradzia',
        'institutions' => 'atstovavimas',
        'meetings' => 'atstovavimas',
        'agendaItems' => 'atstovavimas',
        'documents' => 'atstovavimas',
        'problems' => 'atstovavimas',
        'goals' => 'atstovavimas',
        'reservations' => 'rezervacijos',
        'resources' => 'rezervacijos',
        'pages' => 'svetaine',
        'news' => 'svetaine',
        'calendars' => 'svetaine',
        'banners' => 'svetaine',
        'navigations' => 'svetaine',
        'quickLinks' => 'svetaine',
        'eventTypes' => 'svetaine',
        'tags' => 'svetaine',
        'files' => 'svetaine',
        'studySets' => 'svetaine',
        'users' => 'organizacija',
        'duties' => 'organizacija',
        'dutiables' => 'organizacija',
        'studyPrograms' => 'organizacija',
        'mailingLists' => 'organizacija',
        'learningSteps' => 'organizacija',
        'forms' => 'organizacija',
        'roles' => 'sistema',
        'permissions' => 'sistema',
        'institutionTypes' => 'sistema',
        'dutyTypes' => 'sistema',
        'relationships' => 'sistema',
        'settings' => 'sistema',
        'knowledgeItems' => 'wiki',
    ];

    /** Resource => translation key for the `own` scope dropdown label. */
    private const array OWN_SCOPE_LABELS = [
        'duties' => 'access.own_duties',
        'dutiables' => 'access.own_duties',
        'meetings' => 'access.own_institutions',
        'documents' => 'access.own_institution_documents',
    ];

    /** Resources carrying per-cell scope explanations under `access.permission_scopes.<resource>`. */
    private const array SCOPE_DESCRIPTION_RESOURCES = ['meetings', 'agendaItems'];

    public static function areaOf(string $resource): string
    {
        return self::RESOURCE_AREAS[$resource] ?? 'sistema';
    }

    /**
     * The editor's payload: resources grouped by workspace in navigation order, with baseline hints.
     *
     * @return array{
     *     areas: list<string>,
     *     resources: list<array{
     *         key: string,
     *         model: string|null,
     *         area: string,
     *         abilities: array<string, list<string>>,
     *         baseline: string|null,
     *         baselineSegments: list<array{text: string, strong: bool}>,
     *         scopeLabels: array<string, string>,
     *         scopeDescriptions: array<string, array<string, string>>,
     *         softDeletes: bool,
     *     }>,
     *     retired: list<string>,
     * }
     */
    public static function toFrontend(): array
    {
        $baseline = BaselineAccess::descriptions();
        $areas = AdminNavigationCatalog::WORKSPACE_KEYS;
        $areaOrder = array_flip($areas);
        $resourceOrder = array_flip(array_keys(self::RESOURCE_AREAS));

        $resources = [];
        foreach (PermissionCatalog::abilities() as $resource => $abilities) {
            /** @var array<string, array<string, string>> $scopeDescriptions */
            $scopeDescriptions = in_array($resource, self::SCOPE_DESCRIPTION_RESOURCES, true)
                ? __("access.permission_scopes.{$resource}")
                : [];

            $resources[] = [
                'key' => $resource,
                'model' => ModelEnum::tryFromPlural($resource)?->value,
                'area' => self::areaOf($resource),
                'abilities' => $abilities,
                'baseline' => $baseline[$resource] ?? null,
                'baselineSegments' => BaselineAccess::segments($resource),
                'scopeLabels' => isset(self::OWN_SCOPE_LABELS[$resource]) ? ['own' => (string) __(self::OWN_SCOPE_LABELS[$resource])] : [],
                'scopeDescriptions' => $scopeDescriptions,
                'softDeletes' => ModelEnum::isSoftDeletable($resource),
            ];
        }

        usort($resources, static fn (array $a, array $b): int => [
            $areaOrder[$a['area']] ?? PHP_INT_MAX,
            $resourceOrder[$a['key']] ?? PHP_INT_MAX,
        ] <=> [
            $areaOrder[$b['area']] ?? PHP_INT_MAX,
            $resourceOrder[$b['key']] ?? PHP_INT_MAX,
        ]);

        return [
            'areas' => $areas,
            'resources' => $resources,
            'retired' => BaselineAccess::retiredPermissionNames(),
        ];
    }
}
