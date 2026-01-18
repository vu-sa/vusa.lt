<template>
  <div class="space-y-4">
    <!-- Filter Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-foreground">
        {{ $t('Filtrai') }}
      </h3>
      <Button
        v-if="activeFilterCount > 0"
        variant="ghost"
        size="sm"
        class="text-xs"
        @click="$emit('clearFilters')"
      >
        <RotateCcw class="size-3 mr-1" />
        {{ $t('Išvalyti') }} ({{ activeFilterCount }})
      </Button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="space-y-2">
        <div class="h-10 bg-muted animate-pulse rounded-lg" />
        <div class="space-y-1 pl-2">
          <div v-for="j in 3" :key="j" class="h-6 bg-muted/50 animate-pulse rounded" />
        </div>
      </div>
    </div>

    <!-- Facets Accordion -->
    <Accordion
      v-else
      type="multiple"
      :default-value="defaultOpenFacets"
      class="space-y-3"
    >
      <FilterAccordion
        v-for="facet in facets"
        :key="facet.field"
        :value="facet.field"
        :label="facet.label"
        :icon="getFacetIcon(facet.icon)"
        :badge-count="getSelectedCount(facet.field)"
        variant="desktop"
      >
        <!-- Year Pills -->
        <template v-if="facet.type === 'year-pills'">
          <YearFilter
            :values="facet.values"
            :selected-values="getSelectedValues(facet.field) as number[]"
            @toggle="(year) => $emit('toggleFilter', facet.field, year)"
          />
        </template>

        <!-- Checkbox List -->
        <template v-else-if="facet.type === 'checkbox'">
          <CheckboxFilter
            :options="facet.values"
            :selected-values="getSelectedValues(facet.field) as string[]"
            :max-visible="facetConfig.fields.find(f => f.field === facet.field)?.maxValues"
            :label-formatter="(value) => getLabelForValue(facet.field, value)"
            @toggle="(value) => $emit('toggleFilter', facet.field, value)"
          />
        </template>

        <!-- Radio (single select) -->
        <template v-else-if="facet.type === 'radio'">
          <FacetRadioGroup
            :values="facet.values"
            :selected-value="(getSelectedValues(facet.field)[0] as string)"
            :label-formatter="(value) => getLabelForValue(facet.field, value)"
            @select="(value) => $emit('setFilter', facet.field, value)"
          />
        </template>
      </FilterAccordion>
    </Accordion>

    <!-- Empty State -->
    <div
      v-if="!isLoading && facets.length === 0"
      class="text-center py-8 text-muted-foreground"
    >
      <p class="text-sm">{{ $t('Nėra filtrų') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, markRaw } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Calendar,
  CheckCircle,
  Vote,
  Building2,
  Users,
  ThumbsUp,
  Gavel,
  UserCheck,
  Globe,
  Filter,
  RotateCcw,
} from 'lucide-vue-next'

import { Button } from '@/Components/ui/button'
import { Accordion } from '@/Components/ui/accordion'

import { CheckboxFilter, YearFilter, FilterAccordion } from '@/Components/Shared/Search'
import type { AdminFacet, AdminSearchFilters, CollectionFacetConfig } from '../Types/AdminSearchTypes'
import { getFacetValueLabel } from '../Config/collectionFacetConfig'

import FacetRadioGroup from './Facets/FacetRadioGroup.vue'

interface Props {
  facets: AdminFacet[]
  filters: AdminSearchFilters
  facetConfig: CollectionFacetConfig
  isLoading?: boolean
  activeFilterCount?: number
}

interface Emits {
  (e: 'toggleFilter', field: string, value: string | number): void
  (e: 'setFilter', field: string, value: unknown): void
  (e: 'clearFilters'): void
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  activeFilterCount: 0,
})

defineEmits<Emits>()

// Icon map - mark raw to avoid reactive proxy issues with functional components
const iconMap: Record<string, typeof Calendar> = {
  Calendar: markRaw(Calendar),
  CheckCircle: markRaw(CheckCircle),
  Vote: markRaw(Vote),
  Building2: markRaw(Building2),
  Users: markRaw(Users),
  ThumbsUp: markRaw(ThumbsUp),
  Gavel: markRaw(Gavel),
  UserCheck: markRaw(UserCheck),
  Globe: markRaw(Globe),
  Filter: markRaw(Filter),
}

const fallbackIcon = iconMap.Filter

// Get icon component for facet
const getFacetIcon = (iconName?: string): typeof Calendar => {
  if (!iconName) {
    console.warn('[AdminFacetSidebar] Missing icon name for facet.', {
      facets: props.facets.map(f => ({ field: f.field, icon: f.icon })),
    })
    return fallbackIcon
  }

  const resolvedIcon = iconMap[iconName]

  if (!resolvedIcon) {
    console.warn('[AdminFacetSidebar] Unknown icon name.', {
      iconName,
      availableIcons: Object.keys(iconMap),
    })
  }

  return resolvedIcon || fallbackIcon
}

// Get default open facets from config
const defaultOpenFacets = computed(() => {
  return props.facetConfig.fields
    .filter(f => f.defaultOpen)
    .map(f => f.field)
})

// Get selected values for a field
const getSelectedValues = (field: string): (string | number)[] => {
  const value = props.filters[field]
  if (Array.isArray(value)) {
    return value
  }
  if (value !== undefined && value !== null) {
    return [value as string | number]
  }
  return []
}

// Get selected count for a field
const getSelectedCount = (field: string): number => {
  const values = getSelectedValues(field)
  return values.length
}

// Get human-readable label for a facet value
const getLabelForValue = (field: string, value: string): string => {
  return getFacetValueLabel(field, value)
}
</script>
