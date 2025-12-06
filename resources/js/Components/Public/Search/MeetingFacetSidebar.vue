<template>
  <div class="space-y-4">
    <!-- Mobile Filter Button -->
    <div class="lg:hidden mb-4">
      <Sheet v-model:open="isMobileFiltersOpen">
        <SheetTrigger as-child>
          <Button variant="outline" class="w-full">
            <Filter class="w-4 h-4 mr-2" />
            {{ $t('search.filters') }}
            <Badge v-if="activeFilterCount > 0" variant="secondary" class="ml-2">
              {{ activeFilterCount }}
            </Badge>
          </Button>
        </SheetTrigger>
        <SheetContent side="left" class="w-[300px] sm:w-[350px] px-6">
          <SheetHeader class="border-b border-border/20 pb-4 px-0">
            <div class="text-left">
              <SheetTitle class="text-xl font-bold text-foreground flex items-center gap-2">
                <Filter class="h-5 w-5 text-primary" />
                {{ $t('search.filter_meetings') }}
              </SheetTitle>
            </div>
          </SheetHeader>
          <ScrollArea class="h-full pr-4">
            <div class="mt-8 pb-32">
              <!-- Mobile filters content - same as desktop but in sheet -->
              <div class="space-y-6">
                <!-- Filter Header -->
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold">
                    {{ $t('search.filters') }}
                  </h3>
                  <Button variant="ghost" size="sm" :disabled="activeFilterCount === 0" class="text-xs"
                    @click="emit('clearFilters')">
                    <RotateCcw class="w-3 h-3 mr-1" />
                    <template v-if="activeFilterCount > 0">
                      {{ $t('search.clear_filters_count', { count: activeFilterCount }) }}
                    </template>
                    <template v-else>
                      {{ $t('search.clear_filters') }}
                    </template>
                  </Button>
                </div>

                <!-- Main Filters -->
                <Accordion type="multiple" :default-value="['tenants', 'completion', 'years', 'dates']" class="w-full">
                  <!-- Tenant Filter -->
                  <AccordionItem value="tenants">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.tenants') }}</span>
                        <Badge v-if="filters.tenants.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.tenants.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <TenantFilter :tenant-hierarchy="processedTenantHierarchy" :selected-tenants="filters.tenants"
                        @toggle-tenant="(tenant: string) => emit('update:tenant', tenant)" />
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Institution Type Filter (Mobile) -->
                  <AccordionItem v-if="institutionTypeFacet?.values?.length" value="institution-types">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <Layers class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.institution_type') }}</span>
                        <Badge v-if="filters.institutionTypes.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.institutionTypes.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <div class="space-y-2">
                        <label v-for="type in institutionTypeFacet?.values || []" :key="type.value" :class="[
                          'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors hover:bg-accent',
                          filters.institutionTypes.includes(type.value) ? 'bg-accent' : ''
                        ]">
                          <Checkbox :model-value="filters.institutionTypes.includes(type.value)"
                            @update:model-value="emit('update:institutionType', type.value)" />
                          <div class="flex items-center gap-2 flex-1">
                            <span class="text-sm font-medium">
                              {{ type.label }}
                            </span>
                            <Badge variant="outline" class="ml-auto text-xs">
                              {{ formatCount(type.count) }}
                            </Badge>
                          </div>
                        </label>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Completion Status Filter -->
                  <AccordionItem value="completion">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <FileCheck class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.completion_status') }}</span>
                        <Badge v-if="filters.completionStatus.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.completionStatus.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <div class="space-y-2">
                        <label v-for="status in completionStatusFacet?.values || []" :key="status.value" :class="[
                          'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors hover:bg-accent',
                          filters.completionStatus.includes(status.value) ? 'bg-accent' : ''
                        ]">
                          <Checkbox :model-value="filters.completionStatus.includes(status.value)"
                            @update:model-value="emit('update:completionStatus', status.value)" />
                          <div class="flex items-center gap-2 flex-1">
                            <span class="text-sm font-medium">
                              {{ status.label }}
                            </span>
                            <Badge variant="outline" class="ml-auto text-xs">
                              {{ formatCount(status.count) }}
                            </Badge>
                          </div>
                        </label>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Years Filter -->
                  <AccordionItem value="years">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.years') }}</span>
                        <Badge v-if="filters.years.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.years.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <div class="space-y-2">
                        <label v-for="year in yearFacet?.values || []" :key="year.value" :class="[
                          'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors hover:bg-accent',
                          filters.years.includes(Number(year.value)) ? 'bg-accent' : ''
                        ]">
                          <Checkbox :model-value="filters.years.includes(Number(year.value))"
                            @update:model-value="emit('update:year', Number(year.value))" />
                          <div class="flex items-center gap-2 flex-1">
                            <span class="text-sm font-medium">
                              {{ year.value }}
                            </span>
                            <Badge variant="outline" class="ml-auto text-xs">
                              {{ formatCount(year.count) }}
                            </Badge>
                          </div>
                        </label>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Success Rate Filter -->
                  <AccordionItem value="success-rate">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <TrendingUp class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.success_rate') }}</span>
                        <Badge v-if="filters.successRateRanges.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.successRateRanges.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <div class="space-y-2">
                        <label v-for="range in successRateOptions" :key="range.value" :class="[
                          'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors hover:bg-accent',
                          filters.successRateRanges.includes(range.value) ? 'bg-accent' : ''
                        ]">
                          <Checkbox :model-value="filters.successRateRanges.includes(range.value)"
                            @update:model-value="emit('update:successRate', range.value)" />
                          <span class="text-sm font-medium">
                            {{ range.label }}
                          </span>
                        </label>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Date Range Filter -->
                  <AccordionItem value="dates">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.date') }}</span>
                        <Badge
                          v-if="(filters.dateRange.preset && filters.dateRange.preset !== 'recent') || filters.dateRange.from || filters.dateRange.to"
                          variant="secondary" class="ml-auto mr-2">
                          1
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <DateRangeFilter :date-range="filters.dateRange"
                        @update:date-range="(range: any) => emit('update:dateRange', range)" />
                    </AccordionContent>
                  </AccordionItem>
                </Accordion>
              </div>
            </div>
          </ScrollArea>
        </SheetContent>
      </Sheet>
    </div>

    <!-- Desktop Filters -->
    <div class="hidden lg:block">
      <div class="space-y-4">
        <!-- Filter Header -->
        <div class="flex items-start justify-between pt-3 pb-5">
          <div>
            <h3 class="text-xl font-bold text-foreground tracking-tight">
              {{ $t('search.filters') }}
            </h3>
          </div>
          <Button variant="ghost" size="sm" :disabled="activeFilterCount === 0"
            class="text-xs font-medium shrink-0 ml-4" @click="emit('clearFilters')">
            <RotateCcw class="w-3 h-3 mr-1.5" />
            <template v-if="activeFilterCount > 0">
              {{ $t('search.clear_filters_count', { count: activeFilterCount }) }}
            </template>
            <template v-else>
              {{ $t('search.clear_filters') }}
            </template>
          </Button>
        </div>

        <!-- Main Filters -->
        <Accordion type="multiple" :default-value="['tenants', 'completion', 'years', 'dates']" class="w-full space-y-3">
          <!-- Tenant Filter - Primary Focus -->
          <AccordionItem value="tenants"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div class="p-1.5 rounded-lg bg-primary/10 text-primary group-hover:bg-primary/15 transition-colors">
                  <Building2 class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.tenants') }}</span>
                </div>
                <Badge v-if="filters.tenants.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.tenants.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 3" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <TenantFilter v-else :tenant-hierarchy="processedTenantHierarchy" :selected-tenants="filters.tenants"
                @toggle-tenant="(tenant: string) => emit('update:tenant', tenant)" />
            </AccordionContent>
          </AccordionItem>

          <!-- Institution Type Filter -->
          <AccordionItem v-if="institutionTypeFacet?.values?.length" value="institution-types"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div class="p-1.5 rounded-lg bg-teal-500/10 text-teal-600 group-hover:bg-teal-500/15 transition-colors">
                  <Layers class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.institution_type') }}</span>
                </div>
                <Badge v-if="filters.institutionTypes.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.institutionTypes.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 3" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <div v-else class="space-y-2">
                <label v-for="type in institutionTypeFacet?.values || []" :key="type.value" :class="[
                  'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:bg-accent/50 hover:border-accent-foreground/20',
                  filters.institutionTypes.includes(type.value) ? 'bg-accent border-accent-foreground/20 shadow-sm' : 'hover:shadow-sm'
                ]">
                  <Checkbox :model-value="filters.institutionTypes.includes(type.value)"
                    class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    @update:model-value="emit('update:institutionType', type.value)" />
                  <div class="flex items-center justify-between flex-1">
                    <span class="font-medium text-sm text-foreground">
                      {{ type.label }}
                    </span>
                    <Badge variant="outline" class="text-xs font-medium">
                      {{ formatCount(type.count) }}
                    </Badge>
                  </div>
                </label>
              </div>
            </AccordionContent>
          </AccordionItem>

          <!-- Completion Status Filter -->
          <AccordionItem value="completion"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-600 group-hover:bg-blue-500/15 transition-colors">
                  <FileCheck class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.completion_status') }}</span>
                </div>
                <Badge v-if="filters.completionStatus.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.completionStatus.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 3" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <div v-else class="space-y-2">
                <label v-for="status in completionStatusFacet?.values || []" :key="status.value" :class="[
                  'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:bg-accent/50 hover:border-accent-foreground/20',
                  filters.completionStatus.includes(status.value) ? 'bg-accent border-accent-foreground/20 shadow-sm' : 'hover:shadow-sm'
                ]">
                  <Checkbox :model-value="filters.completionStatus.includes(status.value)"
                    class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    @update:model-value="emit('update:completionStatus', status.value)" />
                  <div class="flex items-center justify-between flex-1">
                    <span class="font-medium text-sm text-foreground">
                      {{ status.label }}
                    </span>
                    <Badge variant="outline" class="text-xs font-medium">
                      {{ formatCount(status.count) }}
                    </Badge>
                  </div>
                </label>
              </div>
            </AccordionContent>
          </AccordionItem>

          <!-- Years Filter -->
          <AccordionItem value="years"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div
                  class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 group-hover:bg-emerald-500/15 transition-colors">
                  <CalendarDays class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.years') }}</span>
                </div>
                <Badge v-if="filters.years.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.years.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 3" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <div v-else class="space-y-2">
                <label v-for="year in yearFacet?.values || []" :key="year.value" :class="[
                  'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:bg-accent/50 hover:border-accent-foreground/20',
                  filters.years.includes(Number(year.value)) ? 'bg-accent border-accent-foreground/20 shadow-sm' : 'hover:shadow-sm'
                ]">
                  <Checkbox :model-value="filters.years.includes(Number(year.value))"
                    class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    @update:model-value="emit('update:year', Number(year.value))" />
                  <div class="flex items-center justify-between flex-1">
                    <span class="font-medium text-sm text-foreground">
                      {{ year.value }}
                    </span>
                    <Badge variant="outline" class="text-xs font-medium">
                      {{ formatCount(year.count) }}
                    </Badge>
                  </div>
                </label>
              </div>
            </AccordionContent>
          </AccordionItem>

          <!-- Success Rate Filter -->
          <AccordionItem value="success-rate"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div
                  class="p-1.5 rounded-lg bg-violet-500/10 text-violet-600 group-hover:bg-violet-500/15 transition-colors">
                  <TrendingUp class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.success_rate') }}</span>
                </div>
                <Badge v-if="filters.successRateRanges.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.successRateRanges.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div class="space-y-2">
                <label v-for="range in successRateOptions" :key="range.value" :class="[
                  'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:bg-accent/50 hover:border-accent-foreground/20',
                  filters.successRateRanges.includes(range.value) ? 'bg-accent border-accent-foreground/20 shadow-sm' : 'hover:shadow-sm'
                ]">
                  <Checkbox :model-value="filters.successRateRanges.includes(range.value)"
                    class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    @update:model-value="emit('update:successRate', range.value)" />
                  <div class="flex items-center justify-between flex-1">
                    <span class="font-medium text-sm text-foreground">
                      {{ range.label }}
                    </span>
                  </div>
                </label>
              </div>
            </AccordionContent>
          </AccordionItem>

          <!-- Date Range Filter -->
          <AccordionItem value="dates"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div
                  class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15 transition-colors">
                  <Calendar class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.date') }}</span>
                </div>
                <Badge
                  v-if="(filters.dateRange.preset && filters.dateRange.preset !== 'recent') || filters.dateRange.from || filters.dateRange.to"
                  variant="default" class="font-medium text-xs px-2 py-1">
                  1
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <DateRangeFilter :date-range="filters.dateRange"
                @update:date-range="(range: any) => emit('update:dateRange', range)" />
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// ShadcnVue components
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetHeader,
  SheetTitle
} from '@/Components/ui/sheet'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/Components/ui/accordion'
import { Checkbox } from '@/Components/ui/checkbox'
import { ScrollArea } from '@/Components/ui/scroll-area'

// Icons
import {
  Filter,
  Building2,
  Layers,
  FileCheck,
  Calendar,
  CalendarDays,
  TrendingUp,
  RotateCcw
} from 'lucide-vue-next'

// Local components
import TenantFilter from './TenantFilter.vue'
import DateRangeFilter from './DateRangeFilter.vue'

// Composables
import type { MeetingFacet, MeetingSearchFilters } from '@/Composables/useMeetingSearch'

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
  (e: 'update:completionStatus', status: string): void
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

// Local state
const isMobileFiltersOpen = ref(false)

// Success rate options
const successRateOptions = [
  { value: 'high', label: $t('search.success_rate_high') },
  { value: 'medium', label: $t('search.success_rate_medium') },
  { value: 'low', label: $t('search.success_rate_low') }
]

// Utility functions for facet processing
const formatCount = (count: number): string => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  }
  if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

const processTenantFacet = (facet: MeetingFacet | undefined) => {
  if (!facet?.values) {
    return { main: [], padaliniai: [], pkp: [] }
  }

  const main: any[] = []
  const padaliniai: any[] = []
  const pkp: any[] = []

  facet.values.forEach(tenant => {
    const value = tenant.value

    // Transform facet object to match TenantFilter expectations
    const transformedTenant = {
      shortname: value,
      name: value,
      type: 'tenant',
      count: tenant.count
    }

    // VU SA main organization - ONLY the exact match
    if (value === 'VU SA') {
      main.push(transformedTenant)
    }
    // PKP (programs, clubs, projects) - check this first before padaliniai
    else if (value.includes('PKP')) {
      pkp.push(transformedTenant)
    }
    // VU SA Padaliniai (faculty units) - all other VU SA units
    else if (value.startsWith('VU SA ')) {
      padaliniai.push(transformedTenant)
    }
    // Other organizations (non VU SA) - everything else
    else {
      main.push(transformedTenant)
    }
  })

  const result = {
    main: main.sort((a, b) => b.count - a.count),
    padaliniai: padaliniai.sort((a, b) => b.count - a.count),
    pkp: pkp.sort((a, b) => b.count - a.count)
  }

  return result
}

// Computed properties
const tenantFacet = computed(() => {
  return props.facets.find(f => f.field === 'tenant_shortname')
})

const institutionTypeFacet = computed(() => {
  return props.facets.find(f => f.field === 'institution_type_title')
})

const completionStatusFacet = computed(() => {
  return props.facets.find(f => f.field === 'completion_status')
})

const yearFacet = computed(() => {
  const facet = props.facets.find(f => f.field === 'year')
  // Sort years in descending order (newest first)
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
