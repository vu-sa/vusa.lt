<template>
  <FilterSidebar
    :active-filter-count
    mobile-title="search.institution_filters"
    @clear-filters="emit('clearFilters')"
  >
    <template #mobile-filters>
      <Accordion type="multiple" :default-value="['tenants', 'types']" class="w-full">
        <!-- Tenant Filter -->
        <FilterAccordion
          value="tenants"
          :label="$t('search.tenants')"
          :icon="Building2"
          :badge-count="filters.tenants.length"
          variant="mobile"
        >
          <TenantFilter
            :tenant-hierarchy="processedTenantHierarchy"
            :selected-tenants="filters.tenants"
            @toggle-tenant="(tenant: string) => emit('update:tenant', tenant)"
          />
        </FilterAccordion>

        <!-- Institution Type Filter -->
        <FilterAccordion
          value="types"
          :label="$t('search.institution_type')"
          :icon="Tag"
          :badge-count="filters.types.length"
          variant="mobile"
        >
          <CheckboxFilter
            :options="typeOptions"
            :selected-values="filters.types"
            :max-visible="0"
            empty-text="Nėra tipų"
            @toggle="(value) => emit('update:type', String(value))"
          />
        </FilterAccordion>
      </Accordion>
    </template>

    <template #desktop-filters>
      <Accordion type="multiple" :default-value="['tenants', 'types']" class="w-full space-y-3">
        <!-- Tenant Filter -->
        <FilterAccordion
          value="tenants"
          :label="$t('search.tenants')"
          :icon="Building2"
          :badge-count="filters.tenants.length"
          :is-loading
          icon-container-class="bg-primary/10 text-primary group-hover:bg-primary/15"
        >
          <TenantFilter
            :tenant-hierarchy="processedTenantHierarchy"
            :selected-tenants="filters.tenants"
            @toggle-tenant="(tenant: string) => emit('update:tenant', tenant)"
          />
        </FilterAccordion>

        <!-- Institution Type Filter -->
        <FilterAccordion
          value="types"
          :label="$t('search.institution_type')"
          :icon="Tag"
          :badge-count="filters.types.length"
          :is-loading
          icon-container-class="bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15"
        >
          <CheckboxFilter
            :options="typeOptions"
            :selected-values="filters.types"
            :max-visible="10"
            container-class="max-h-[300px] overflow-y-auto"
            empty-text="Nėra tipų"
            @toggle="(value) => emit('update:type', String(value))"
          />
        </FilterAccordion>
      </Accordion>
    </template>
  </FilterSidebar>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, Tag } from 'lucide-vue-next';

// Shared Search Components
import TenantFilter from './TenantFilter.vue';

import { FilterSidebar, FilterAccordion, CheckboxFilter } from '@/Components/Shared/Search';

// ShadcnVue components
import { Accordion } from '@/Components/ui/accordion';

// Local components

// Types
import type { InstitutionFacet, InstitutionSearchFilters } from '@/Types/InstitutionSearchTypes';

interface Props {
  facets: InstitutionFacet[];
  filters: InstitutionSearchFilters;
  isLoading?: boolean;
  activeFilterCount?: number;
  typeLabels?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  activeFilterCount: 0,
  typeLabels: () => ({}),
});

const emit = defineEmits<{
  'update:tenant': [tenant: string];
  'update:type': [type: string];
  'clearFilters': [];
}>();

// Extract tenant facet for processing
const tenantFacet = computed(() =>
  props.facets.find(f => f.field === 'tenant_shortname'),
);

// Extract type facet
const typeFacet = computed(() =>
  props.facets.find(f => f.field === 'type_slugs'),
);

// Transform type facet to FilterOption format
const typeOptions = computed(() => {
  if (!typeFacet.value?.values) return [];
  return typeFacet.value.values.map(v => ({
    value: v.value,
    label: props.typeLabels[v.value] || v.value,
    count: v.count,
  }));
});

// Process tenant hierarchy for TenantFilter
const processedTenantHierarchy = computed(() => {
  if (!tenantFacet.value?.values) {
    return { main: [], padaliniai: [], pkp: [] };
  }

  const tenants = tenantFacet.value.values.map(tenant => ({
    shortname: tenant.value,
    name: tenant.value,
    fullname: tenant.value,
    type: tenant.value === 'VU SA' ? 'main' : tenant.value.startsWith('VU SA ') ? 'padalinys' : 'other',
    count: tenant.count,
    isSelected: props.filters.tenants.includes(tenant.value),
  }));

  const main = tenants.filter(t => t.shortname === 'VU SA');
  const padaliniai = tenants.filter(t => t.shortname !== 'VU SA' && t.shortname.startsWith('VU SA '));
  const pkp = tenants.filter(t => !t.shortname.startsWith('VU SA'));

  return { main, padaliniai, pkp };
});
</script>
