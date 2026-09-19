<?php

namespace App\Services\AdminNavigation;

/**
 * One destination inside a workspace — a tab in the section row, a row in the workspace picker
 * panel, an entry in *Visi skyriai*.
 */
final readonly class Section
{
    /**
     * @param  string  $labelKey  i18n key (lang/admin/{lt,en}/shell.php), resolved with `$t()`
     *                            in Vue — never a literal string, so the cached payload does not
     *                            need to vary by locale.
     * @param  array<string, mixed>  $routeParams
     * @param  string|null  $entityType  A `ModelEnum` value, for icon + category colour
     *                                   (`Constants/entityTypes.ts`). Null for sections with no
     *                                   backing entity (an overview, settings, system status).
     * @param  list<CollectionAction>  $collectionActions
     * @param  list<string>  $matches  Route-name patterns (`Route::is()` style) that live inside this
     *                                 section, so a record page resolves to its workspace. Empty means
     *                                 the default: `x.index` claims `x.*`, any other name only itself.
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public string $routeName,
        public array $routeParams,
        public ?string $entityType,
        public Visibility $visibility,
        public array $collectionActions = [],
        public array $matches = [],
    ) {}

    /**
     * @return array{key: string, label: string, routeName: string, routeParams: array<string, mixed>, entityType: string|null, collectionActions: list<array{key: string, label: string, target: string}>, matches: list<string>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->labelKey,
            'routeName' => $this->routeName,
            'routeParams' => $this->routeParams,
            'entityType' => $this->entityType,
            'collectionActions' => array_map(fn (CollectionAction $action) => $action->toArray(), $this->collectionActions),
            'matches' => $this->routePatterns(),
        ];
    }

    /**
     * @return list<string>
     */
    private function routePatterns(): array
    {
        if ($this->matches !== []) {
            return $this->matches;
        }

        return [str_ends_with($this->routeName, '.index') ? substr($this->routeName, 0, -5).'*' : $this->routeName];
    }
}
