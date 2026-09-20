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
     * @param  Section|null  $overview  The workspace's Apžvalga, for workspaces whose overview is
     *                                  not gated on a model of its own: it is prepended exactly
     *                                  when at least one other section is visible, so "any section
     *                                  below" lives in one place instead of a second gate that
     *                                  would have to be kept in step with the list.
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public string $descriptionKey,
        public array $sections,
        public array $createActions = [],
        public ?Section $overview = null,
    ) {}
}
