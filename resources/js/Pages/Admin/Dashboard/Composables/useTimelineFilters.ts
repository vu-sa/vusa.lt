import { ref, computed, onMounted } from 'vue';
import type { AtstovavimosInstitution, AtstovavimosTenant, TimelineFilters } from '../types';

export function useTimelineFilters(
  institutions: AtstovavimosInstitution[],
  availableTenants: AtstovavimosTenant[]
) {
  // Filter state
  const userTenantFilter = ref<string[]>([]);
  const showOnlyWithActivityUser = ref(false);
  const showOnlyWithActivityTenant = ref(false);
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

  // Filter functions
  function toggleUserTenantFilter(id: string, checked: boolean) {
    const set = new Set(userTenantFilter.value);
    if (checked) set.add(id);
    else set.delete(id);
    userTenantFilter.value = Array.from(set);
  }

  function toggleTenantForGantt(id: string, checked: boolean) {
    const set = new Set(selectedTenantForGantt.value);
    if (checked) set.add(id);
    else set.delete(id);
    selectedTenantForGantt.value = Array.from(set);
  }

  function handleTenantUpdateValue(value: string) {
    // Legacy function for backward compatibility, now toggles tenant
    const isSelected = selectedTenantForGantt.value.includes(value);
    toggleTenantForGantt(value, !isSelected);
  }

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

  // Export filter state for serialization
  const getFilterState = (): TimelineFilters => ({
    userTenantFilter: userTenantFilter.value,
    showOnlyWithActivityUser: showOnlyWithActivityUser.value,
    showOnlyWithActivityTenant: showOnlyWithActivityTenant.value,
    selectedTenantForGantt: selectedTenantForGantt.value
  });

  return {
    // State
    userTenantFilter,
    showOnlyWithActivityUser,
    showOnlyWithActivityTenant,
    selectedTenantForGantt,
    
    // Computed
    availableTenantsUser,
    currentTenant,
    
    // Actions
    toggleUserTenantFilter,
    toggleTenantForGantt,
    setSelectedTenants,
    handleTenantUpdateValue,
    getFilterState
  };
}
