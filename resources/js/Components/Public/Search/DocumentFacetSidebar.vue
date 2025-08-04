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
                {{ $t('search.filter_documents') }}
              </SheetTitle>
            </div>
          </SheetHeader>
          <ScrollArea class="h-full pr-4">
            <div class="mt-8 pb-6">
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
                <Accordion type="multiple" :default-value="['tenants', 'languages', 'dates']" class="w-full">
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

                  <!-- Content Type Filter -->
                  <AccordionItem value="content-types">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <FileText class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.document_type') }}</span>
                        <Badge v-if="filters.contentTypes.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.contentTypes.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <ContentTypeFilter :grouped-types="groupedContentTypes" :selected-types="filters.contentTypes"
                        @toggle-type="(type: string) => emit('update:contentType', type)" />
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Language and Date filters for mobile -->
                  <AccordionItem value="languages">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <Globe class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.language') }}</span>
                        <Badge v-if="filters.languages.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.languages.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <div class="space-y-2">
                        <template v-if="languageFacet?.values?.length">
                          <label v-for="lang in languageFacet.values" :key="lang.value" :class="[
                            'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors hover:bg-accent',
                            filters.languages.includes(lang.value) ? 'bg-accent' : ''
                          ]">
                            <Checkbox :model-value="filters.languages.includes(lang.value)"
                              @update:model-value="emit('update:language', lang.value)" />
                            <div class="flex items-center gap-2 flex-1">
                              <img 
                                v-if="getLanguageFlag(lang.value)"
                                :src="getLanguageFlag(lang.value)" 
                                :alt="`${getLanguageDisplay(lang.value)} flag`"
                                width="16" 
                                class="rounded-full flex-shrink-0"
                              />
                              <span class="text-sm font-medium">
                                {{ getLanguageDisplay(lang.value) }}
                              </span>
                              <Badge variant="outline" class="ml-auto text-xs">
                                {{ formatCount(lang.count) }}
                              </Badge>
                            </div>
                          </label>
                        </template>
                        <div v-else class="text-sm text-muted-foreground p-2">
                          {{ $t('search.language_filters_after_search') }}
                        </div>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

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
        <Accordion type="multiple" :default-value="['tenants', 'languages', 'dates']" class="w-full space-y-3">
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

          <!-- Content Type Filter -->
          <AccordionItem value="content-types"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-600 group-hover:bg-blue-500/15 transition-colors">
                  <FileText class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.document_type') }}</span>
                  <p class="text-xs text-muted-foreground mt-0.5">
                    {{ $t('search.document_type_description') }}
                  </p>
                </div>
                <Badge v-if="filters.contentTypes.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.contentTypes.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 4" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <ContentTypeFilter v-else :grouped-types="groupedContentTypes" :selected-types="filters.contentTypes"
                @toggle-type="(type: string) => emit('update:contentType', type)" />
            </AccordionContent>
          </AccordionItem>

          <!-- Language Filter -->
          <AccordionItem value="languages"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div
                  class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 group-hover:bg-emerald-500/15 transition-colors">
                  <Globe class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.language') }}</span>
                  <p class="text-xs text-muted-foreground mt-0.5">
                    {{ $t('search.language_description') }}
                  </p>
                </div>
                <Badge v-if="filters.languages.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.languages.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 2" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <div v-else class="space-y-2">
                <template v-if="languageFacet?.values?.length">
                  <label v-for="lang in languageFacet.values" :key="lang.value" :class="[
                    'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:bg-accent/50 hover:border-accent-foreground/20',
                    filters.languages.includes(lang.value) ? 'bg-accent border-accent-foreground/20 shadow-sm' : 'hover:shadow-sm'
                  ]">
                    <Checkbox :model-value="filters.languages.includes(lang.value)"
                      class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                      @update:model-value="emit('update:language', lang.value)" />
                    <div class="flex items-center justify-between flex-1">
                      <div class="flex items-center gap-2">
                        <img 
                          v-if="getLanguageFlag(lang.value)"
                          :src="getLanguageFlag(lang.value)" 
                          :alt="`${getLanguageDisplay(lang.value)} flag`"
                          width="16" 
                          class="rounded-full flex-shrink-0"
                        />
                        <span class="font-medium text-sm text-foreground">
                          {{ getLanguageDisplay(lang.value) }}
                        </span>
                      </div>
                      <Badge variant="outline" class="text-xs font-medium">
                        {{ formatCount(lang.count) }}
                      </Badge>
                    </div>
                  </label>
                </template>
                <div v-else class="text-sm text-muted-foreground p-3 text-center italic">
                  {{ $t('search.language_filters_after_search') }}
                </div>
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
                  <p class="text-xs text-muted-foreground mt-0.5">
                    {{ $t('search.document_creation_time') }}
                  </p>
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
import { usePage } from '@inertiajs/vue3'
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
  FileText,
  Globe,
  Calendar,
  RotateCcw
} from 'lucide-vue-next'

// Local components
import TenantFilter from './TenantFilter.vue'
import ContentTypeFilter from './ContentTypeFilter.vue'
import DateRangeFilter from './DateRangeFilter.vue'

// Composables
import type { DocumentFacet, DocumentSearchFilters } from '@/Composables/useDocumentSearch'

// Props interface
interface Props {
  facets: DocumentFacet[]
  filters: DocumentSearchFilters
  isLoading?: boolean
  activeFilterCount?: number
}

interface Emits {
  (e: 'update:tenant', tenant: string): void
  (e: 'update:contentType', contentType: string): void
  (e: 'update:language', language: string): void
  (e: 'update:dateRange', range: DocumentSearchFilters['dateRange']): void
  (e: 'clearFilters'): void
  (e: 'applyPreset', preset: any): void
}

const props = withDefaults(defineProps<Props>(), {
  facets: () => [],
  isLoading: false,
  activeFilterCount: 0
})

const emit = defineEmits<Emits>()

// Local state
const isMobileFiltersOpen = ref(false)
const page = usePage()

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

const processTenantFacet = (facet: DocumentFacet | undefined) => {
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
      name: value, // For now, use the same value. You might want to add proper name mapping later
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

const groupContentTypes = (facet: DocumentFacet | undefined) => {
  if (!facet?.values) {
    return { vusa: [], vusaP: [], other: [] }
  }

  const vusa: any[] = []
  const vusaP: any[] = []
  const other: any[] = []

  facet.values.forEach(type => {
    const value = type.value

    // VU SA P (Padalinių) documents - CHECK THIS FIRST
    if (value.includes('VU SA P ')) {
      // For VU SA P, the label will have "VU SA P " removed, so capitalize what remains
      const remainingText = value.replace(/^VU SA P /, '')
      const capitalizedLabel = remainingText.charAt(0).toUpperCase() + remainingText.slice(1)
      const capitalizedType = {
        ...type,
        label: capitalizedLabel
      }
      vusaP.push(capitalizedType)
    }
    // VU SA main organization documents - CHECK THIS SECOND
    else if (value.includes('VU SA')) {
      // For VU SA, the label will have "VU SA " removed, so capitalize what remains
      const remainingText = value.replace(/^VU SA /, '')
      const capitalizedLabel = remainingText.charAt(0).toUpperCase() + remainingText.slice(1)
      const capitalizedType = {
        ...type,
        label: capitalizedLabel
      }
      vusa.push(capitalizedType)
    }
    // Everything else goes to other
    else {
      // For other, use the full value and capitalize it
      const capitalizedLabel = value.charAt(0).toUpperCase() + value.slice(1)
      const capitalizedType = {
        ...type,
        label: capitalizedLabel
      }
      other.push(capitalizedType)
    }
  })

  const result = {
    vusa: vusa.sort((a, b) => b.count - a.count),
    vusaP: vusaP.sort((a, b) => b.count - a.count),
    other: other.sort((a, b) => b.count - a.count)
  }

  return result
}

// Language display helpers
const getLanguageFlag = (languageValue: string): string => {
  if (languageValue === 'Lietuvių' || languageValue === 'Lithuanian') return 'https://hatscripts.github.io/circle-flags/flags/lt.svg'
  if (languageValue === 'Anglų' || languageValue === 'English') return 'https://hatscripts.github.io/circle-flags/flags/gb.svg'
  return '' // For Unknown or other languages - no flag
}

const getLanguageDisplay = (languageValue: string): string => {
  if (languageValue === 'Lietuvių' || languageValue === 'Lithuanian') return 'LT'
  if (languageValue === 'Anglų' || languageValue === 'English') return 'EN'
  if (languageValue === 'Unknown') return $t('search.language_unknown')
  return languageValue // For any other language values, show as-is
}

// Computed properties
const tenantFacet = computed(() => {
  const facet = props.facets.find(f => f.field === 'tenant_shortname')
  return facet
})

const contentTypeFacet = computed(() => {
  const facet = props.facets.find(f => f.field === 'content_type')
  return facet
})

const languageFacet = computed(() => {
  const facet = props.facets.find(f => f.field === 'language')
  return facet
})

const processedTenantHierarchy = computed(() => {
  const result = processTenantFacet(tenantFacet.value)
  return result
})

const groupedContentTypes = computed(() => {
  const result = groupContentTypes(contentTypeFacet.value)
  return result
})

// Computed properties for processing facets
</script>
