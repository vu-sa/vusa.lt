<?php

namespace App\Services\AdminNavigation;

/**
 * A top-level destination in the admin shell (ViSAK, Rezervacijos, Svetainė, …).
 *
 * A workspace carries no `Visibility` of its own — it is visible exactly when at least one of
 * its sections is (resolved in `AdminNavigationCatalog`), so there is nothing to keep in sync
 * between a workspace-level check and its sections.
 */
final readonly class Workspace
{
    /**
     * @param  string  $labelKey  i18n key for the workspace name (the picker trigger, the tab).
     * @param  string  $descriptionKey  i18n key for the one-line description in the picker panel (O25).
     * @param  list<Section>  $sections
     * @param  list<CreateAction>  $createActions
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public string $descriptionKey,
        public array $sections,
        public array $createActions = [],
    ) {}
}
