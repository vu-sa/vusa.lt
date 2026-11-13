import { ref, watch, type Ref } from 'vue';

export function useCollectionControls(isDesktop: Ref<boolean>, filtersOpen: Ref<boolean>, loadFacets: () => void) {
  const sheetOpen = ref(false);
  const toggleFilters = () => {
    if (isDesktop.value) filtersOpen.value = !filtersOpen.value;
    else sheetOpen.value = true;
  };
  watch(() => isDesktop.value ? filtersOpen.value : sheetOpen.value, (open) => {
    if (open) loadFacets();
  }, { immediate: true });
  return { sheetOpen, toggleFilters };
}
