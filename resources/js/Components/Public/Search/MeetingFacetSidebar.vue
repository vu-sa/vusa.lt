<template>
  <FilterSidebar
    :active-filter-count="activeFilterCount"
    mobile-title="search.filter_meetings"
    @clear-filters="emit('clearFilters')"
  >
    <template #mobile-filters>
      <Accordion type="multiple" :default-value="['tenants', 'years', 'dates']" class="w-full">
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

        <!-- Institution Type Filter (Mobile) -->
        <FilterAccordion
          v-if="institutionTypeFacet?.values?.length"
          value="institution-types"
          :label="$t('search.institution_type')"
          :icon="Layers"
          :badge-count="filters.institutionTypes.length"
          variant="mobile"
        >
          <CheckboxFilter
            :options="institutionTypeOptions"
            :selected-values="filters.institutionTypes"
            :max-visible="0"
            @toggle="(value) => emit('update:institutionType', String(value))"
          />
        </FilterAccordion>

        <!-- Years Filter (Mobile) -->
        <FilterAccordion
          value="years"
          :label="$t('search.years')"
          :icon="CalendarDays"
          :badge-count="filters.years.length"
          variant="mobile"
        >
          <YearFilter
            :values="yearFacet?.values || []"
            :selected-values="filters.years"
            :max-visible="0"
            @toggle="(year) => emit('update:year', year)"
          />
        </FilterAccordion>

        <!-- Success Rate Filter (Mobile) -->
        <FilterAccordion
          value="success-rate"
          :label="$t('search.success_rate')"
          :icon="TrendingUp"
          :badge-count="filters.successRateRanges.length"
          variant="mobile"
        >
          <CheckboxFilter
            :options="successRateOptions"
            :selected-values="filters.successRateRanges"
            :show-counts="false"
            :max-visible="0"
            @toggle="(value) => emit('update:successRate', String(value))"
          />
        </FilterAccordion>

        <!-- Date Range Filter (Mobile) -->
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
      <Accordion type="multiple" :default-value="['tenants', 'years', 'dates']" class="w-full space-y-3">
        <!-- Tenant Filter -->
        <FilterAccordion
          value="tenants"
          :label="$t('search.tenants')"
          :icon="Building2"
          :badge-count="filters.tenants.length"
          :is-loading="isLoading"
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
          v-if="institutionTypeFacet?.values?.length"
          value="institution-types"
          :label="$t('search.institution_type')"
          :icon="Layers"
          :badge-count="filters.institutionTypes.length"
          :is-loading="isLoading"
          icon-container-class="bg-teal-500/10 text-teal-600 group-hover:bg-teal-500/15"
        >
          <CheckboxFilter
            :options="institutionTypeOptions"
            :selected-values="filters.institutionTypes"
            :max-visible="0"
            @toggle="(value) => emit('update:institutionType', String(value))"
          />
        </FilterAccordion>

        <!-- Years Filter -->
        <FilterAccordion
          value="years"
          :label="$t('search.years')"
          :icon="CalendarDays"
          :badge-count="filters.years.length"
          :is-loading="isLoading"
          icon-container-class="bg-emerald-500/10 text-emerald-600 group-hover:bg-emerald-500/15"
        >
          <YearFilter
            :values="yearFacet?.values || []"
            :selected-values="filters.years"
            :max-visible="8"
            @toggle="(year) => emit('update:year', year)"
          />
        </FilterAccordion>

        <!-- Success Rate Filter -->
        <FilterAccordion
          value="success-rate"
          :label="$t('search.success_rate')"
          :icon="TrendingUp"
          :badge-count="filters.successRateRanges.length"
          icon-container-class="bg-violet-500/10 text-violet-600 group-hover:bg-violet-500/15"
        >
          <CheckboxFilter
            :options="successRateOptions"
            :selected-values="filters.successRateRanges"
            :show-counts="false"
            :max-visible="0"
            @toggle="(value) => emit('update:successRate', String(value))"
          />
        </FilterAccordion>

        <!-- Date Range Filter -->
        <FilterAccordion
          value="dates"
          :label="$t('search.date')"
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
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// Shared Search Components
import { FilterSidebar, FilterAccordion, CheckboxFilter, YearFilter } from '@/Components/Shared/Search'

// ShadcnVue components
import { Accordion } from '@/Components/ui/accordion'

// Icons
import {
  Building2,
  Layers,
  Calendar,
  CalendarDays,
  TrendingUp,
} from 'lucide-vue-next'

// Local components
import TenantFilter from './TenantFilter.vue'
import DateRangeFilter from './DateRangeFilter.vue'

// Types
import type { MeetingFacet, MeetingSearchFilters } from '@/Types/MeetingSearchTypes'

// Props interface
interface Props {
  facets: MeetingFacet[]
  filters: MeetingSearchFilters
  isLoading?: boolean
  activeFilterCount?: number
}

interface Emits {
  (e: 'update:tenant', tenant: string): void
  (e: 'update:institutionType', type: string): void
  (e: 'update:year', year: number): void
  (e: 'update:successRate', range: string): void
  (e: 'update:dateRange', range: MeetingSearchFilters['dateRange']): void
  (e: 'clearFilters'): void
}

const props = withDefaults(defineProps<Props>(), {
  facets: () => [],
  isLoading: false,
  activeFilterCount: 0
})

const emit = defineEmits<Emits>()

// Success rate options
const successRateOptions = [
  { value: 'high', label: $t('search.success_rate_high') },
  { value: 'medium', label: $t('search.success_rate_medium') },
  { value: 'low', label: $t('search.success_rate_low') }
]

// Access filters from props
const filters = computed(() => props.filters)

// Check if date filter is active
const hasDateFilter = computed(() => {
  return (filters.value.dateRange.preset && filters.value.dateRange.preset !== 'recent') ||
    filters.value.dateRange.from ||
    filters.value.dateRange.to
})

// Process tenant facet into hierarchy for TenantFilter
const processTenantFacet = (facet: MeetingFacet | undefined) => {
  if (!facet?.values) {
    return { main: [], padaliniai: [], pkp: [] }
  }

  const main: any[] = []
  const padaliniai: any[] = []
  const pkp: any[] = []

  facet.values.forEach(tenant => {
    const value = tenant.value

    const transformedTenant = {
      shortname: value,
      name: value,
      type: 'tenant',
      count: tenant.count
    }

    if (value === 'VU SA') {
      main.push(transformedTenant)
    } else if (value.includes('PKP')) {
      pkp.push(transformedTenant)
    } else if (value.startsWith('VU SA ')) {
      padaliniai.push(transformedTenant)
    } else {
      main.push(transformedTenant)
    }
  })

  return {
    main: main.sort((a, b) => b.count - a.count),
    padaliniai: padaliniai.sort((a, b) => b.count - a.count),
    pkp: pkp.sort((a, b) => b.count - a.count)
  }
}

// Computed facets
const tenantFacet = computed(() => {
  return props.facets.find(f => f.field === 'tenant_shortname')
})

const institutionTypeFacet = computed(() => {
  return props.facets.find(f => f.field === 'institution_type_title')
})

// Transform institution type facet to FilterOption format
const institutionTypeOptions = computed(() => {
  return (institutionTypeFacet.value?.values || []).map(v => ({
    value: v.value,
    label: v.label || v.value,
    count: v.count
  }))
})

const yearFacet = computed(() => {
  const facet = props.facets.find(f => f.field === 'year')
  if (facet?.values) {
    return {
      ...facet,
      values: [...facet.values].sort((a, b) => Number(b.value) - Number(a.value))
    }
  }
  return facet
})

const processedTenantHierarchy = computed(() => {
  return processTenantFacet(tenantFacet.value)
})
</script>
