import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { BreadcrumbItem } from '@/Composables/useBreadcrumbsUnified';

type Catalog = NonNullable<PageProps['adminNavigation']>;
export type AdminWorkspace = Catalog['workspaces'][number];
export type AdminSection = AdminWorkspace['sections'][number];

export interface ActiveNavigation {
  workspace: AdminWorkspace | undefined;
  section: AdminSection | undefined;
}

export const SHELL_PREFETCH_CACHE_FOR: [string, string] = ['15s', '1m'];

export const sectionHref = (section: AdminSection): string => route(section.routeName, section.routeParams);

/** Where a workspace opens: its first section, which is always its overview. */
export const workspaceHref = (workspace: AdminWorkspace): string | undefined =>
  workspace.sections[0] ? sectionHref(workspace.sections[0]) : undefined;

const PRIMARY_TIE_BREAKER = 'atstovavimas';

const escapeRegExp = (value: string): string => value.replace(/[.+?^$()|{}[\]\\]/g, '\\$&');

const patternMatches = (pattern: string, routeName: string): boolean =>
  new RegExp(`^${pattern.split('*').map(escapeRegExp).join('.*')}$`).test(routeName);

/**
 * Which workspace and section a route belongs to, so a record page keeps its tab lit. A section
 * carrying `routeParams` claims a route only when every param agrees (that is what tells the
 * registration forms from Formos on `forms.show`), and an exact route name beats a wildcard.
 * Mirrors `catalogCandidates()` in AdminNavigationCatalogTest.
 */
export function resolveActive(
  workspaces: AdminWorkspace[],
  routeName: string | undefined,
  params: Record<string, unknown> = {},
): ActiveNavigation {
  if (!routeName) {
    return { workspace: undefined, section: undefined };
  }

  const candidates = workspaces.flatMap(workspace => workspace.sections.flatMap((section) => {
    const pattern = (section.matches ?? []).find(candidate => patternMatches(candidate, routeName));
    const paramsAgree = Object.entries(section.routeParams).every(([key, value]) => String(params[key] ?? '') === String(value));

    return pattern !== undefined && paramsAgree
      ? [{ workspace, section, withParams: Object.keys(section.routeParams).length > 0, exact: pattern === routeName }]
      : [];
  }));

  const best = candidates.reduce<(typeof candidates)[number] | undefined>((winner, candidate) => {
    if (!winner) {
      return candidate;
    }

    const rank = (item: typeof candidate) => Number(item.withParams) * 2 + Number(item.exact);

    return rank(candidate) > rank(winner) ? candidate : winner;
  }, undefined);

  return { workspace: best?.workspace, section: best?.section };
}

const pathOf = (href: string | undefined): string | undefined => {
  if (!href) {
    return undefined;
  }

  try {
    return new URL(href, 'http://shell.invalid').pathname.replace(/\/$/, '') || '/';
  }
  catch {
    return undefined;
  }
};

/**
 * The part of a page's breadcrumb trail that the shell still has to draw: from the active
 * section down. Home and Administravimas are what the top bar and picker already are, and the
 * section itself is the tab row, so a trail that ends at the section (an index page) draws
 * nothing — breadcrumbs exist only *below* section level (record → sub-record).
 *
 * The section stays as the first crumb so it doubles as the way back. When a section is active
 * but absent from the trail, the trail was left over from another page (index pages without
 * breadcrumbs of their own don't reset it), so nothing is drawn rather than something wrong.
 */
export function belowSectionTrail(
  crumbs: readonly BreadcrumbItem[],
  activeSection: AdminSection | undefined,
  outerPaths: readonly string[] = [],
): BreadcrumbItem[] {
  const outer = new Set(outerPaths.map(pathOf));
  const trail = crumbs.filter(crumb => !outer.has(pathOf(crumb.href)));

  if (activeSection) {
    const sectionPath = pathOf(sectionHref(activeSection));
    const at = trail.findIndex(crumb => pathOf(crumb.href) === sectionPath);
    const below = at === -1 ? [] : trail.slice(at);

    return below.length > 1 ? below : [];
  }

  return trail.length > 1 ? trail : [];
}

/**
 * The workspace a phone's bottom bar puts beside Pradžia: the one holding the most sections
 * (navigation.md), Pradžia itself excluded, ties going to ViSAK and then catalog order.
 */
export function primaryWorkspace(workspaces: AdminWorkspace[]): AdminWorkspace | undefined {
  return workspaces
    .filter(workspace => workspace.key !== 'pradzia')
    .reduce<AdminWorkspace | undefined>((winner, workspace) => {
      if (!winner || workspace.sections.length > winner.sections.length) {
        return workspace;
      }

      return workspace.sections.length === winner.sections.length && workspace.key === PRIMARY_TIE_BREAKER ? workspace : winner;
    }, undefined);
}

// Pages outside every section (profile, Visi skyriai) keep the last workspace instead of
// snapping back to Pradžia mid-task.
const lastWorkspaceKey = ref<string>();

function currentRoute(): { name: string | undefined; params: Record<string, unknown> } {
  try {
    const current = route();

    return { name: current.current(), params: current.params ?? {} };
  }
  catch {
    return { name: undefined, params: {} };
  }
}

export function useAdminNavigation() {
  const page = usePage<PageProps>();
  const workspaces = computed(() => page.props.adminNavigation?.workspaces ?? []);
  const sections = computed(() => workspaces.value.flatMap(workspace => workspace.sections));

  const sectionForRoute = (routeName: string): AdminSection | undefined => sections.value.find(section => section.routeName === routeName);
  const hasCollectionAction = (routeName: string, key: string): boolean =>
    sectionForRoute(routeName)?.collectionActions.some(action => action.key === key) ?? false;

  // `page.url` is read only to re-run on navigation; Ziggy's `route()` is not reactive.
  const resolved = computed(() => {
    void page.url;
    const { name, params } = currentRoute();

    return resolveActive(workspaces.value, name, params);
  });

  watch(resolved, (active) => {
    if (active.workspace) {
      lastWorkspaceKey.value = active.workspace.key;
    }
  }, { immediate: true });

  const activeWorkspace = computed(() => resolved.value.workspace
    ?? workspaces.value.find(workspace => workspace.key === lastWorkspaceKey.value)
    ?? workspaces.value[0]);
  const activeSection = computed(() => resolved.value.section);
  const primary = computed(() => primaryWorkspace(workspaces.value));

  return { workspaces, sections, sectionForRoute, hasCollectionAction, activeWorkspace, activeSection, primaryWorkspace: primary };
}
