import { ref, computed, onMounted } from 'vue';
import type { AtstovavimosInstitution, AtstovavimosTenant } from '../types';

export function useTimelineFilters(
  institutions: AtstovavimosInstitution[],
  availableTenants: AtstovavimosTenant[]
) {
  // Filter state
  const userTenantFilter = ref<string[]>([]);
  const showOnlyWithActivityUser = ref(false);
  const showOnlyWithActivityTenant = ref(false);
  const showOnlyWithPublicMeetingsUser = ref(false);
  const showOnlyWithPublicMeetingsTenant = ref(false);
  const selectedTenantForGantt = ref<string[]>([]); // Changed to array for multiple selection

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

  // Initialize on mount - select first available tenant by default
  onMounted(() => {
    // Default to first tenant if available
    if (availableTenants.length > 0 && selectedTenantForGantt.value.length === 0) {
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
    selectedTenantForGantt,
    
    // Computed
    availableTenantsUser,
    currentTenant,
    
    // Actions
    setSelectedTenants,
  };
}
