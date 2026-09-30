/**
 * useTimelineFilters - Shared timeline filter state with Provide/Inject
 *
 * This composable provides centralized filter state management for Gantt charts
 * across UserTimelineSection and TenantTimelineSection.
 *
 * Uses the same pattern as useGanttSettings for consistency.
 *
 * Usage:
 * - In parent (ShowAtstovavimas.vue): call provideTimelineFilters()
 * - In children: call useTimelineFilters() to access shared state
 */
import { computed, inject, provide, ref, watch, type InjectionKey, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';

import type { AtstovavimasInstitution, AtstovavimasTenant } from '../types';

import { TenantType } from '@/Types/enums';

const STORAGE_KEY = 'atstovavimas-timeline-filters';

export interface TimelineFilters {
  // User section filters
  userTenantFilter: Ref<string[]>;
  showOnlyWithActivityUser: Ref<boolean>;
  showOnlyWithPublicMeetingsUser: Ref<boolean>;
  /** Hide VU SA's own bodies. Off by default — the chart shows everything unless asked. */
  hideInternalInstitutionsUser: Ref<boolean>;
  showDutyMembersUser: Ref<boolean>;
  showRelatedInstitutionsUser: Ref<boolean>;
  relatedInstitutionsLoaded: Ref<boolean>;

  // Tenant section filters
  selectedTenantForGantt: Ref<string[]>;
  /** The padaliniai the statistics count — only ones the user manages, apart from the Gantt's. */
  selectedStatsTenants: Ref<string[]>;
  showOnlyWithActivityTenant: Ref<boolean>;
  showOnlyWithPublicMeetingsTenant: Ref<boolean>;
  hideInternalInstitutionsTenant: Ref<boolean>;
  showDutyMembersTenant: Ref<boolean>;
  showActivityStatusTenant: Ref<boolean>;
  tenantInstitutionsLoaded: Ref<boolean>;
  tenantInstitutionsLoading: Ref<boolean>;

  // Shared state
  scrollPosition: Ref<number>;

  // Computed
  availableTenantsUser: Ref<AtstovavimasTenant[]>;
  currentTenant: Ref<AtstovavimasTenant | undefined>;

  // Actions
  setSelectedTenants: (tenantIds: string[]) => void;
  setSelectedStatsTenants: (tenantIds: string[]) => void;
  setUserTenantFilter: (tenantIds: string[]) => void;
  resetTenantFilters: () => void;
  resetUserFilters: () => void;
  loadRelatedInstitutions: () => void;
}

interface StoredFilters {
  selectedTenantForGantt: string[];
  selectedStatsTenants?: string[];
  userTenantFilter: string[];
  showOnlyWithActivityTenant: boolean;
  showOnlyWithPublicMeetingsTenant: boolean;
  hideInternalInstitutionsTenant: boolean;
  showDutyMembersTenant: boolean;
  showActivityStatusTenant: boolean;
  showOnlyWithActivityUser: boolean;
  showOnlyWithPublicMeetingsUser: boolean;
  hideInternalInstitutionsUser: boolean;
  showDutyMembersUser: boolean;
  showRelatedInstitutionsUser: boolean;
  scrollPosition?: number;
}

const TIMELINE_FILTERS_KEY: InjectionKey<TimelineFilters> = Symbol('timeline-filters');

export function normalizeTenantSelection(
  tenantIds: string[],
  availableTenants: AtstovavimasTenant[],
  fallback: 'first' | 'all' | string[] = 'first',
): string[] {
  const selectedIds = new Set(tenantIds.map(String));
  const availableIds = availableTenants.map(tenant => String(tenant.id));
  const validSelection = availableIds.filter(id => selectedIds.has(id));

  if (validSelection.length > 0) {
    return validSelection;
  }

  if (availableIds.length === 0) {
    return [];
  }

  if (Array.isArray(fallback)) {
    return normalizeTenantSelection(fallback, availableTenants, 'first');
  }

  return fallback === 'all' ? availableIds : [availableIds[0]];
}

export function getInstitutionTenants(
  institutions: AtstovavimasInstitution[],
): AtstovavimasTenant[] {
  const tenants = new Map<string, AtstovavimasTenant>();

  institutions.forEach((institution) => {
    const { tenant } = institution;

    if (!tenant) {
      return;
    }

    tenants.set(String(tenant.id), {
      id: tenant.id,
      shortname: tenant.shortname,
      type: tenant.type ?? TenantType.Padalinys,
    });
  });

  return Array.from(tenants.values()).sort((left, right) =>
    left.shortname.localeCompare(right.shortname),
  );
}

/**
 * Creates and provides timeline filter state to child components.
 * Call this once in the parent component (e.g., ShowAtstovavimas.vue).
 */
export function provideTimelineFilters(
  institutions: AtstovavimasInstitution[],
  availableTenants: AtstovavimasTenant[],
  options: { statsTenants?: AtstovavimasTenant[]; defaultGanttTenantIds?: string[] } = {},
): TimelineFilters {
  const statsTenants = options.statsTenants ?? availableTenants;
  const defaultGanttTenantIds = options.defaultGanttTenantIds ?? [];
  const stored = useStorage<StoredFilters>(
    STORAGE_KEY,
    () => ({}),
    undefined,
    { flush: 'sync', onError: () => {} },
  );

  // User section filters
  const availableTenantsUser = computed(() => getInstitutionTenants(institutions));
  const userTenantFilter = ref<string[]>(
    normalizeTenantSelection(
      stored.value.userTenantFilter ?? [],
      availableTenantsUser.value,
      'all',
    ),
  );
  const showOnlyWithActivityUser = ref(stored.value.showOnlyWithActivityUser ?? false);
  const showOnlyWithPublicMeetingsUser = ref(stored.value.showOnlyWithPublicMeetingsUser ?? false);
  const hideInternalInstitutionsUser = ref(stored.value.hideInternalInstitutionsUser ?? false);
  const showDutyMembersUser = ref(stored.value.showDutyMembersUser ?? true);
  // Default to false - related institutions are lazy loaded when filter is enabled
  const showRelatedInstitutionsUser = ref(stored.value.showRelatedInstitutionsUser ?? false);
  // Track if related institutions have been loaded via Inertia lazy
  const relatedInstitutionsLoaded = ref(false);

  // Tenant section filters
  const selectedTenantForGantt = ref<string[]>(
    normalizeTenantSelection(stored.value.selectedTenantForGantt ?? [], availableTenants, defaultGanttTenantIds),
  );
  const selectedStatsTenants = ref<string[]>(
    normalizeTenantSelection(stored.value.selectedStatsTenants ?? stored.value.selectedTenantForGantt ?? [], statsTenants),
  );
  const showOnlyWithActivityTenant = ref(stored.value.showOnlyWithActivityTenant ?? false);
  const showOnlyWithPublicMeetingsTenant = ref(stored.value.showOnlyWithPublicMeetingsTenant ?? false);
  const hideInternalInstitutionsTenant = ref(stored.value.hideInternalInstitutionsTenant ?? false);
  const showDutyMembersTenant = ref(stored.value.showDutyMembersTenant ?? true);
  // Default to false - activity status rings are off by default to keep Gantt clean
  const showActivityStatusTenant = ref(stored.value.showActivityStatusTenant ?? false);
  // Track if tenant institutions have been loaded via Inertia lazy
  const tenantInstitutionsLoaded = ref(false);
  const tenantInstitutionsLoading = ref(false);

  // Shared state
  const scrollPosition = ref<number>(stored.value.scrollPosition ?? 0);

  // Computed: current selected tenant for display
  const currentTenant = computed(() =>
    selectedTenantForGantt.value.length > 0
      ? availableTenants.find(t => String(t.id) === selectedTenantForGantt.value[0])
      : undefined,
  );

  // Persist filters on change
  function persistFilters() {
    stored.value = {
      selectedTenantForGantt: selectedTenantForGantt.value,
      selectedStatsTenants: selectedStatsTenants.value,
      userTenantFilter: userTenantFilter.value,
      showOnlyWithActivityTenant: showOnlyWithActivityTenant.value,
      showOnlyWithPublicMeetingsTenant: showOnlyWithPublicMeetingsTenant.value,
      hideInternalInstitutionsTenant: hideInternalInstitutionsTenant.value,
      showDutyMembersTenant: showDutyMembersTenant.value,
      showActivityStatusTenant: showActivityStatusTenant.value,
      showOnlyWithActivityUser: showOnlyWithActivityUser.value,
      showOnlyWithPublicMeetingsUser: showOnlyWithPublicMeetingsUser.value,
      hideInternalInstitutionsUser: hideInternalInstitutionsUser.value,
      showDutyMembersUser: showDutyMembersUser.value,
      showRelatedInstitutionsUser: showRelatedInstitutionsUser.value,
      scrollPosition: scrollPosition.value,
    };
  }

  watch([
    selectedTenantForGantt,
    selectedStatsTenants,
    userTenantFilter,
    showOnlyWithActivityTenant,
    showOnlyWithPublicMeetingsTenant,
    hideInternalInstitutionsTenant,
    showDutyMembersTenant,
    showActivityStatusTenant,
    showOnlyWithActivityUser,
    showOnlyWithPublicMeetingsUser,
    hideInternalInstitutionsUser,
    showDutyMembersUser,
    showRelatedInstitutionsUser,
    scrollPosition,
  ], () => {
    persistFilters();
  }, { deep: true });

  function setSelectedTenants(tenantIds: string[]) {
    selectedTenantForGantt.value = normalizeTenantSelection(tenantIds, availableTenants, defaultGanttTenantIds);
  }

  function setSelectedStatsTenants(tenantIds: string[]) {
    selectedStatsTenants.value = normalizeTenantSelection(tenantIds, statsTenants);
  }

  function setUserTenantFilter(tenantIds: string[]) {
    userTenantFilter.value = normalizeTenantSelection(
      tenantIds,
      availableTenantsUser.value,
      'all',
    );
  }

  function resetTenantFilters() {
    showOnlyWithActivityTenant.value = false;
    showOnlyWithPublicMeetingsTenant.value = false;
    hideInternalInstitutionsTenant.value = false;
    showDutyMembersTenant.value = true;
    showActivityStatusTenant.value = false;
    scrollPosition.value = 0;
  }

  function resetUserFilters() {
    showOnlyWithActivityUser.value = false;
    showOnlyWithPublicMeetingsUser.value = false;
    hideInternalInstitutionsUser.value = false;
    showDutyMembersUser.value = true;
    showRelatedInstitutionsUser.value = false;
  }

  watch(availableTenantsUser, (tenants) => {
    userTenantFilter.value = normalizeTenantSelection(
      userTenantFilter.value,
      tenants,
      'all',
    );
  });

  // Load related institutions via Inertia lazy reload
  function loadRelatedInstitutions() {
    if (relatedInstitutionsLoaded.value) return;

    router.reload({
      only: ['relatedInstitutions'],
      onSuccess: () => {
        relatedInstitutionsLoaded.value = true;
      },
    });
  }

  const filters: TimelineFilters = {
    // User section filters
    userTenantFilter,
    showOnlyWithActivityUser,
    showOnlyWithPublicMeetingsUser,
    hideInternalInstitutionsUser,
    showDutyMembersUser,
    showRelatedInstitutionsUser,
    relatedInstitutionsLoaded,
    // Tenant section filters
    selectedTenantForGantt,
    selectedStatsTenants,
    showOnlyWithActivityTenant,
    showOnlyWithPublicMeetingsTenant,
    hideInternalInstitutionsTenant,
    showDutyMembersTenant,
    showActivityStatusTenant,
    tenantInstitutionsLoaded,
    tenantInstitutionsLoading,
    // Shared state
    scrollPosition,
    // Computed
    availableTenantsUser: availableTenantsUser as unknown as Ref<AtstovavimasTenant[]>,
    currentTenant: currentTenant as unknown as Ref<AtstovavimasTenant | undefined>,
    // Actions
    setSelectedTenants,
    setSelectedStatsTenants,
    setUserTenantFilter,
    resetTenantFilters,
    resetUserFilters,
    loadRelatedInstitutions,
  };

  provide(TIMELINE_FILTERS_KEY, filters);

  return filters;
}

/**
 * Injects timeline filter state from the parent component.
 * Call this in child components that need access to shared filter state.
 */
export function useTimelineFilters(): TimelineFilters {
  const filters = inject(TIMELINE_FILTERS_KEY);

  if (!filters) {
    // This should not happen in production if properly set up
    throw new Error('useTimelineFilters: No provider found. Ensure provideTimelineFilters() is called in a parent component.');
  }

  return filters;
}

// Export the injection key for testing purposes
export { TIMELINE_FILTERS_KEY };
