/**
 * useTimelineFilters - Shared timeline filter state with Provide/Inject
 * 
 * This composable provides centralized filter state management for Gantt charts
 * across UserTimelineSection, TenantTimelineSection, and FullscreenGanttModal.
 * 
 * Uses the same pattern as useGanttSettings for consistency.
 * 
 * Usage:
 * - In parent (ShowAtstovavimas.vue): call provideTimelineFilters()
 * - In children: call useTimelineFilters() to access shared state
 */
import { ref, computed, provide, inject, watch, type Ref, type InjectionKey } from 'vue';
import { router } from '@inertiajs/vue3';
import type { AtstovavimosInstitution, AtstovavimosTenant } from '../types';

const STORAGE_KEY = 'atstovavimas-timeline-filters';

export interface TimelineFilters {
  // User section filters
  userTenantFilter: Ref<string[]>;
  showOnlyWithActivityUser: Ref<boolean>;
  showOnlyWithPublicMeetingsUser: Ref<boolean>;
  showDutyMembersUser: Ref<boolean>;
  showRelatedInstitutionsUser: Ref<boolean>;
  relatedInstitutionsLoaded: Ref<boolean>;
  
  // Tenant section filters
  selectedTenantForGantt: Ref<string[]>;
  showOnlyWithActivityTenant: Ref<boolean>;
  showOnlyWithPublicMeetingsTenant: Ref<boolean>;
  showDutyMembersTenant: Ref<boolean>;
  tenantInstitutionsLoaded: Ref<boolean>;
  tenantInstitutionsLoading: Ref<boolean>;
  
  // Shared state
  scrollPosition: Ref<number>;
  
  // Computed
  availableTenantsUser: Ref<AtstovavimosTenant[]>;
  currentTenant: Ref<AtstovavimosTenant | undefined>;
  
  // Actions
  setSelectedTenants: (tenantIds: string[]) => void;
  resetTenantFilters: () => void;
  resetUserFilters: () => void;
  loadRelatedInstitutions: () => void;
  loadTenantInstitutions: (tenantIds?: string[]) => void;
}

interface StoredFilters {
  selectedTenantForGantt: string[];
  showOnlyWithActivityTenant: boolean;
  showOnlyWithPublicMeetingsTenant: boolean;
  showDutyMembersTenant: boolean;
  showOnlyWithActivityUser: boolean;
  showOnlyWithPublicMeetingsUser: boolean;
  showDutyMembersUser: boolean;
  showRelatedInstitutionsUser: boolean;
  scrollPosition?: number;
}

const TIMELINE_FILTERS_KEY: InjectionKey<TimelineFilters> = Symbol('timeline-filters');

function loadStoredFilters(): Partial<StoredFilters> {
  if (typeof window === 'undefined') return {};
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : {};
  } catch {
    return {};
  }
}

function saveStoredFilters(filters: StoredFilters) {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filters));
  } catch {
    // Ignore storage errors
  }
}

/**
 * Creates and provides timeline filter state to child components.
 * Call this once in the parent component (e.g., ShowAtstovavimas.vue).
 */
export function provideTimelineFilters(
  institutions: AtstovavimosInstitution[],
  availableTenants: AtstovavimosTenant[]
): TimelineFilters {
  const stored = loadStoredFilters();

  // User section filters
  const userTenantFilter = ref<string[]>([]);
  const showOnlyWithActivityUser = ref(stored.showOnlyWithActivityUser ?? false);
  const showOnlyWithPublicMeetingsUser = ref(stored.showOnlyWithPublicMeetingsUser ?? false);
  const showDutyMembersUser = ref(stored.showDutyMembersUser ?? true);
  // Default to false - related institutions are lazy loaded when filter is enabled
  const showRelatedInstitutionsUser = ref(stored.showRelatedInstitutionsUser ?? false);
  // Track if related institutions have been loaded via Inertia lazy
  const relatedInstitutionsLoaded = ref(false);

  // Tenant section filters
  const selectedTenantForGantt = ref<string[]>(stored.selectedTenantForGantt ?? []);
  const showOnlyWithActivityTenant = ref(stored.showOnlyWithActivityTenant ?? false);
  const showOnlyWithPublicMeetingsTenant = ref(stored.showOnlyWithPublicMeetingsTenant ?? false);
  const showDutyMembersTenant = ref(stored.showDutyMembersTenant ?? true);
  // Track if tenant institutions have been loaded via Inertia lazy
  const tenantInstitutionsLoaded = ref(false);
  const tenantInstitutionsLoading = ref(false);
  // Track which tenant IDs have been loaded to know when to reload
  const loadedTenantIds = ref<string[]>([]);

  // Shared state
  const scrollPosition = ref<number>(stored.scrollPosition ?? 0);

  // Initialize default tenant selection if needed
  if (selectedTenantForGantt.value.length === 0 && availableTenants.length > 0) {
    selectedTenantForGantt.value = [String(availableTenants[0]?.id)];
  }

  // Computed: available tenants for user section
  const availableTenantsUser = computed(() => {
    const ids = new Set<string>();
    institutions.forEach((i: any) => {
      const tid = String(i?.tenant?.id ?? '');
      if (tid) ids.add(tid);
    });
    return availableTenants.filter(t => ids.has(String(t.id)));
  });

  // Computed: current selected tenant for display
  const currentTenant = computed(() =>
    selectedTenantForGantt.value.length > 0
      ? availableTenants.find(t => String(t.id) === selectedTenantForGantt.value[0])
      : undefined
  );

  // Persist filters on change
  function persistFilters() {
    saveStoredFilters({
      selectedTenantForGantt: selectedTenantForGantt.value,
      showOnlyWithActivityTenant: showOnlyWithActivityTenant.value,
      showOnlyWithPublicMeetingsTenant: showOnlyWithPublicMeetingsTenant.value,
      showDutyMembersTenant: showDutyMembersTenant.value,
      showOnlyWithActivityUser: showOnlyWithActivityUser.value,
      showOnlyWithPublicMeetingsUser: showOnlyWithPublicMeetingsUser.value,
      showDutyMembersUser: showDutyMembersUser.value,
      showRelatedInstitutionsUser: showRelatedInstitutionsUser.value,
      scrollPosition: scrollPosition.value,
    });
  }

  watch([
    selectedTenantForGantt,
    showOnlyWithActivityTenant,
    showOnlyWithPublicMeetingsTenant,
    showDutyMembersTenant,
    showOnlyWithActivityUser,
    showOnlyWithPublicMeetingsUser,
    showDutyMembersUser,
    showRelatedInstitutionsUser,
    scrollPosition
  ], () => {
    persistFilters();
  }, { deep: true });

  function setSelectedTenants(tenantIds: string[]) {
    selectedTenantForGantt.value = tenantIds;
  }

  function resetTenantFilters() {
    showOnlyWithActivityTenant.value = false;
    showOnlyWithPublicMeetingsTenant.value = false;
    showDutyMembersTenant.value = true;
    scrollPosition.value = 0;
    if (availableTenants.length > 0) {
      selectedTenantForGantt.value = [String(availableTenants[0]?.id)];
    } else {
      selectedTenantForGantt.value = [];
    }
  }

  function resetUserFilters() {
    userTenantFilter.value = [];
    showOnlyWithActivityUser.value = false;
    showOnlyWithPublicMeetingsUser.value = false;
    showDutyMembersUser.value = true;
    showRelatedInstitutionsUser.value = false;
  }

  // Load related institutions via Inertia lazy reload
  function loadRelatedInstitutions() {
    if (relatedInstitutionsLoaded.value) return;
    
    router.reload({
      only: ['relatedInstitutions'],
      onSuccess: () => {
        relatedInstitutionsLoaded.value = true;
      }
    });
  }

  // Load tenant institutions via Inertia lazy reload
  // If tenantIds is not provided, uses the current selectedTenantForGantt value
  function loadTenantInstitutions(tenantIds?: string[]) {
    const idsToLoad = tenantIds ?? selectedTenantForGantt.value;
    
    // Skip if no tenants selected
    if (idsToLoad.length === 0) return;
    
    // Skip if already loading
    if (tenantInstitutionsLoading.value) return;
    
    // Check if we need to reload (different tenants selected)
    const idsToLoadSorted = [...idsToLoad].sort();
    const loadedIdsSorted = [...loadedTenantIds.value].sort();
    const sameIds = idsToLoadSorted.length === loadedIdsSorted.length && 
      idsToLoadSorted.every((id, i) => id === loadedIdsSorted[i]);
    
    // Skip if already loaded the same tenants
    if (tenantInstitutionsLoaded.value && sameIds) return;
    
    tenantInstitutionsLoading.value = true;
    
    router.reload({
      only: ['tenantInstitutions'],
      data: { tenantIds: idsToLoad },
      onSuccess: () => {
        tenantInstitutionsLoaded.value = true;
        tenantInstitutionsLoading.value = false;
        loadedTenantIds.value = [...idsToLoad];
      },
      onError: () => {
        tenantInstitutionsLoading.value = false;
      }
    });
  }

  const filters: TimelineFilters = {
    // User section filters
    userTenantFilter,
    showOnlyWithActivityUser,
    showOnlyWithPublicMeetingsUser,
    showDutyMembersUser,
    showRelatedInstitutionsUser,
    relatedInstitutionsLoaded,
    // Tenant section filters
    selectedTenantForGantt,
    showOnlyWithActivityTenant,
    showOnlyWithPublicMeetingsTenant,
    showDutyMembersTenant,
    tenantInstitutionsLoaded,
    tenantInstitutionsLoading,
    // Shared state
    scrollPosition,
    // Computed
    availableTenantsUser: availableTenantsUser as unknown as Ref<AtstovavimosTenant[]>,
    currentTenant: currentTenant as unknown as Ref<AtstovavimosTenant | undefined>,
    // Actions
    setSelectedTenants,
    resetTenantFilters,
    resetUserFilters,
    loadRelatedInstitutions,
    loadTenantInstitutions,
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