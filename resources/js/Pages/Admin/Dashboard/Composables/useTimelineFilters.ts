import { ref, computed, onMounted, watch } from 'vue';
import type { AtstovavimosInstitution, AtstovavimosTenant } from '../types';

const STORAGE_KEY = 'atstovavimas-timeline-filters';

interface StoredFilters {
  selectedTenantForGantt: string[];
  showOnlyWithActivityTenant: boolean;
  showOnlyWithPublicMeetingsTenant: boolean;
  showDutyMembersTenant: boolean;
  scrollPosition?: number;
}

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

export function useTimelineFilters(
  institutions: AtstovavimosInstitution[],
  availableTenants: AtstovavimosTenant[]
) {
  // Load persisted state
  const stored = loadStoredFilters();
  
  // Filter state
  const userTenantFilter = ref<string[]>([]);
  const showOnlyWithActivityUser = ref(false);
  const showOnlyWithActivityTenant = ref(stored.showOnlyWithActivityTenant ?? false);
  const showOnlyWithPublicMeetingsUser = ref(false);
  const showOnlyWithPublicMeetingsTenant = ref(stored.showOnlyWithPublicMeetingsTenant ?? false);
  const showDutyMembersUser = ref(true);
  const showDutyMembersTenant = ref(stored.showDutyMembersTenant ?? true);
  const selectedTenantForGantt = ref<string[]>(stored.selectedTenantForGantt ?? []);
  const scrollPosition = ref<number>(stored.scrollPosition ?? 0);

  // Available tenants for user filtering
  const availableTenantsUser = computed(() => {
    const ids = new Set<string>();
    institutions.forEach((i: any) => {
      const tid = String(i?.tenant?.id ?? '');
      if (tid) ids.add(tid);
    });
    return availableTenants.filter(t => ids.has(String(t.id)));
  });

  // Current selected tenant (for display purposes, shows first selected)
  const currentTenant = computed(() => 
    selectedTenantForGantt.value.length > 0 
      ? availableTenants.find(t => String(t.id) === selectedTenantForGantt.value[0])
      : undefined
  );

  function setSelectedTenants(tenantIds: string[]) {
    selectedTenantForGantt.value = tenantIds;
  }

  // Persist tenant filters to localStorage
  function persistFilters() {
    saveStoredFilters({
      selectedTenantForGantt: selectedTenantForGantt.value,
      showOnlyWithActivityTenant: showOnlyWithActivityTenant.value,
      showOnlyWithPublicMeetingsTenant: showOnlyWithPublicMeetingsTenant.value,
      showDutyMembersTenant: showDutyMembersTenant.value,
      scrollPosition: scrollPosition.value,
    });
  }

  // Watch for changes and persist
  watch([selectedTenantForGantt, showOnlyWithActivityTenant, showOnlyWithPublicMeetingsTenant, showDutyMembersTenant, scrollPosition], () => {
    persistFilters();
  }, { deep: true });

  // Reset filters to defaults
  function resetTenantFilters() {
    showOnlyWithActivityTenant.value = false;
    showOnlyWithPublicMeetingsTenant.value = false;
    showDutyMembersTenant.value = true;
    scrollPosition.value = 0;
    // Reset to first tenant
    if (availableTenants.length > 0) {
      selectedTenantForGantt.value = [String(availableTenants[0]?.id)];
    } else {
      selectedTenantForGantt.value = [];
    }
    // Clear localStorage
    if (typeof window !== 'undefined') {
      localStorage.removeItem(STORAGE_KEY);
    }
  }

  // Reset user filters to defaults
  function resetUserFilters() {
    userTenantFilter.value = [];
    showOnlyWithActivityUser.value = false;
    showOnlyWithPublicMeetingsUser.value = false;
    showDutyMembersUser.value = true;
  }

  // Initialize on mount - only set default tenant if no stored value
  onMounted(() => {
    // If no stored selection and tenants available, default to first tenant
    if (selectedTenantForGantt.value.length === 0 && availableTenants.length > 0) {
      const firstTenantId = String(availableTenants[0]?.id);
      selectedTenantForGantt.value = [firstTenantId];
    }
  });

  return {
    // State
    userTenantFilter,
    showOnlyWithActivityUser,
    showOnlyWithActivityTenant,
    showOnlyWithPublicMeetingsUser,
    showOnlyWithPublicMeetingsTenant,
    showDutyMembersUser,
    showDutyMembersTenant,
    selectedTenantForGantt,
    scrollPosition,
    
    // Computed
    availableTenantsUser,
    currentTenant,
    
    // Actions
    setSelectedTenants,
    resetTenantFilters,
    resetUserFilters,
  };
}
