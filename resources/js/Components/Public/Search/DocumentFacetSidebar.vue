<template>
  <FilterSidebar
    :active-filter-count
    mobile-title="search.filter_documents"
    @clear-filters="emit('clearFilters')"
  >
    <template #mobile-filters>
      <Accordion type="multiple" :default-value="['tenants', 'languages', 'dates']" class="w-full">
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

        <!-- Content Type Filter -->
        <FilterAccordion
          value="content-types"
          :label="$t('search.document_type')"
          :icon="FileText"
          :badge-count="filters.contentTypes.length"
          variant="mobile"
        >
          <ContentTypeFilter
            :grouped-types="groupedContentTypes"
            :selected-types="filters.contentTypes"
            :important-types="importantContentTypes"
            @toggle-type="(type: string) => emit('update:contentType', type)"
          />
        </FilterAccordion>

        <!-- Language Filter -->
        <FilterAccordion
          value="languages"
          :label="$t('search.language')"
          :icon="Globe"
          :badge-count="filters.languages.length"
          variant="mobile"
        >
          <LanguageFilterList
            :languages="languageFacet?.values || []"
            :selected-languages="filters.languages"
            @toggle="(lang) => emit('update:language', lang)"
          />
        </FilterAccordion>

        <!-- Date Range Filter -->
        <FilterAccordion
          value="dates"
          :label="$t('search.date')"
          :icon="Calendar"
          :badge-count="hasDateFilter ? 1 : 0"
          variant="mobile"
        >
          <DateRangeFilter
            :date-range="filters.dateRange"
            @update:date-range="(range: any) => emit('update:dateRange', range)"
          />
        </FilterAccordion>
      </Accordion>
    </template>

    <template #desktop-filters>
      <Accordion type="multiple" :default-value="['tenants', 'languages', 'dates']" class="w-full space-y-3">
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

        <!-- Content Type Filter -->
        <FilterAccordion
          value="content-types"
          :label="$t('search.document_type')"
          :description="$t('search.document_type_description')"
          :icon="FileText"
          :badge-count="filters.contentTypes.length"
          :is-loading
          :skeleton-count="4"
          icon-container-class="bg-blue-500/10 text-blue-600 group-hover:bg-blue-500/15"
        >
          <ContentTypeFilter
            :grouped-types="groupedContentTypes"
            :selected-types="filters.contentTypes"
            :important-types="importantContentTypes"
            @toggle-type="(type: string) => emit('update:contentType', type)"
          />
        </FilterAccordion>

        <!-- Language Filter -->
        <FilterAccordion
          value="languages"
          :label="$t('search.language')"
          :description="$t('search.language_description')"
          :icon="Globe"
          :badge-count="filters.languages.length"
          :is-loading
          :skeleton-count="2"
          icon-container-class="bg-emerald-500/10 text-emerald-600 group-hover:bg-emerald-500/15"
        >
          <LanguageFilterList
            :languages="languageFacet?.values || []"
            :selected-languages="filters.languages"
            @toggle="(lang) => emit('update:language', lang)"
          />
        </FilterAccordion>

        <!-- Date Range Filter -->
        <FilterAccordion
          value="dates"
          :label="$t('search.date')"
          :description="$t('search.document_creation_time')"
          :icon="Calendar"
          :badge-count="hasDateFilter ? 1 : 0"
          icon-container-class="bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15"
        >
          <DateRangeFilter
            :date-range="filters.dateRange"
            @update:date-range="(range: any) => emit('update:dateRange', range)"
          />
        </FilterAccordion>
      </Accordion>
    </template>
  </FilterSidebar>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

// Shared Search Components

// ShadcnVue components

// Icons
import {
  Building2,
  FileText,
  Globe,
  Calendar,
} from 'lucide-vue-next';

// Local components
import TenantFilter from './TenantFilter.vue';
import ContentTypeFilter from './ContentTypeFilter.vue';
import DateRangeFilter from './DateRangeFilter.vue';
import LanguageFilterList from './LanguageFilterList.vue';

import { Accordion } from '@/Components/ui/accordion';
import { FilterSidebar, FilterAccordion } from '@/Components/Shared/Search';

// Types
import type { DocumentFacet, DocumentSearchFilters } from '@/Types/DocumentSearchTypes';

// Props interface
interface Props {
  facets: DocumentFacet[];
  filters: DocumentSearchFilters;
  isLoading?: boolean;
  activeFilterCount?: number;
  importantContentTypes?: string[];
}

interface Emits {
  (e: 'update:tenant', tenant: string): void;
  (e: 'update:contentType', contentType: string): void;
  (e: 'update:language', language: string): void;
  (e: 'update:dateRange', range: DocumentSearchFilters['dateRange']): void;
  (e: 'clearFilters'): void;
  (e: 'applyPreset', preset: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  facets: () => [],
  isLoading: false,
  activeFilterCount: 0,
  importantContentTypes: () => [],
});

const emit = defineEmits<Emits>();

// Check if date filter is active
const hasDateFilter = computed(() => {
  return (props.filters.dateRange.preset && props.filters.dateRange.preset !== 'recent')
    || props.filters.dateRange.from
    || props.filters.dateRange.to;
});

// Tenant processing
const processTenantFacet = (facet: DocumentFacet | undefined) => {
  if (!facet?.values) {
    return { main: [], padaliniai: [], pkp: [] };
  }

  const main: any[] = [];
  const padaliniai: any[] = [];
  const pkp: any[] = [];

  facet.values.forEach((tenant) => {
    const { value } = tenant;

    const transformedTenant = {
      shortname: value,
      name: value,
      type: 'tenant',
      count: tenant.count,
    };

    if (value === 'VU SA') {
      main.push(transformedTenant);
    }
    else if (value.includes('PKP')) {
      pkp.push(transformedTenant);
    }
    else if (value.startsWith('VU SA ')) {
      padaliniai.push(transformedTenant);
    }
    else {
      main.push(transformedTenant);
    }
  });

  return {
    main: main.sort((a, b) => b.count - a.count),
    padaliniai: padaliniai.sort((a, b) => b.count - a.count),
    pkp: pkp.sort((a, b) => b.count - a.count),
  };
};

// Content type grouping
const groupContentTypes = (facet: DocumentFacet | undefined) => {
  if (!facet?.values) {
    return { vusa: [], vusaP: [], other: [] };
  }

  const vusa: any[] = [];
  const vusaP: any[] = [];
  const other: any[] = [];

  facet.values.forEach((type) => {
    const { value } = type;

    if (value.includes('VU SA P ')) {
      const remainingText = value.replace(/^VU SA P /, '');
      const capitalizedLabel = remainingText.charAt(0).toUpperCase() + remainingText.slice(1);
      vusaP.push({ ...type, label: capitalizedLabel });
    }
    else if (value.includes('VU SA')) {
      const remainingText = value.replace(/^VU SA /, '');
      const capitalizedLabel = remainingText.charAt(0).toUpperCase() + remainingText.slice(1);
      vusa.push({ ...type, label: capitalizedLabel });
    }
    else {
      const capitalizedLabel = value.charAt(0).toUpperCase() + value.slice(1);
      other.push({ ...type, label: capitalizedLabel });
    }
  });

  return {
    vusa: vusa.sort((a, b) => b.count - a.count),
    vusaP: vusaP.sort((a, b) => b.count - a.count),
    other: other.sort((a, b) => b.count - a.count),
  };
};

// Computed facets
const tenantFacet = computed(() => {
  return props.facets.find(f => f.field === 'tenant_shortname');
});

const contentTypeFacet = computed(() => {
  return props.facets.find(f => f.field === 'content_type');
});

const languageFacet = computed(() => {
  return props.facets.find(f => f.field === 'language');
});

const processedTenantHierarchy = computed(() => {
  return processTenantFacet(tenantFacet.value);
});

const groupedContentTypes = computed(() => {
  return groupContentTypes(contentTypeFacet.value);
});
</script>
