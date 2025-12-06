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
                {{ $t('search.institution_filters') }}
              </SheetTitle>
            </div>
          </SheetHeader>
          <ScrollArea class="h-full pr-4">
            <div class="mt-8 pb-32">
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
                <Accordion type="multiple" :default-value="['tenants', 'types']" class="w-full">
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
                      <TenantFilter 
                        :tenant-hierarchy="processedTenantHierarchy" 
                        :selected-tenants="filters.tenants"
                        @toggle-tenant="(tenant: string) => emit('update:tenant', tenant)" 
                      />
                    </AccordionContent>
                  </AccordionItem>

                  <!-- Institution Type Filter -->
                  <AccordionItem value="types">
                    <AccordionTrigger class="text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <Tag class="w-4 h-4 text-muted-foreground" />
                        <span>{{ $t('search.institution_type') }}</span>
                        <Badge v-if="filters.types.length > 0" variant="secondary" class="ml-auto mr-2">
                          {{ filters.types.length }}
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent class="pt-2">
                      <div class="space-y-2 max-h-[300px] overflow-y-auto">
                        <template v-if="typeFacet?.values?.length">
                          <label 
                            v-for="type in typeFacet.values" 
                            :key="type.value" 
                            :class="[
                              'flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors hover:bg-accent',
                              filters.types.includes(type.value) ? 'bg-accent' : ''
                            ]"
                          >
                            <Checkbox 
                              :model-value="filters.types.includes(type.value)"
                              @update:model-value="emit('update:type', type.value)" 
                            />
                            <span class="text-sm font-medium flex-1 truncate">{{ getTypeLabel(type.value) }}</span>
                            <Badge variant="outline" class="text-xs shrink-0">
                              {{ formatCount(type.count) }}
                            </Badge>
                          </label>
                        </template>
                        <div v-else class="text-sm text-muted-foreground p-2">
                          {{ $t('search.no_types_available') }}
                        </div>
                      </div>
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
          <Button 
            variant="ghost" 
            size="sm" 
            :disabled="activeFilterCount === 0"
            class="text-xs font-medium shrink-0 ml-4" 
            @click="emit('clearFilters')"
          >
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
        <Accordion type="multiple" :default-value="['tenants', 'types']" class="w-full space-y-3">
          <!-- Tenant Filter -->
          <AccordionItem 
            value="tenants"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200"
          >
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
              <TenantFilter 
                v-else 
                :tenant-hierarchy="processedTenantHierarchy" 
                :selected-tenants="filters.tenants"
                @toggle-tenant="(tenant: string) => emit('update:tenant', tenant)" 
              />
            </AccordionContent>
          </AccordionItem>

          <!-- Institution Type Filter -->
          <AccordionItem 
            value="types"
            class="border border-border/60 rounded-xl bg-card/50 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200"
          >
            <AccordionTrigger class="px-5 py-4 hover:no-underline group">
              <div class="flex items-center gap-3 flex-1">
                <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15 transition-colors">
                  <Tag class="w-4 h-4" />
                </div>
                <div class="flex-1 text-left">
                  <span class="font-semibold text-foreground text-base">{{ $t('search.institution_type') }}</span>
                </div>
                <Badge v-if="filters.types.length > 0" variant="default" class="font-medium text-xs px-2 py-1">
                  {{ filters.types.length }}
                </Badge>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-5 pb-4 pt-1">
              <div v-if="isLoading" class="space-y-1">
                <div v-for="i in 3" :key="i" class="h-6 w-full bg-muted animate-pulse rounded" />
              </div>
              <div v-else class="space-y-1 max-h-[300px] overflow-y-auto">
                <template v-if="typeFacet?.values?.length">
                  <label 
                    v-for="type in typeFacet.values" 
                    :key="type.value" 
                    :class="[
                      'flex items-center gap-2.5 p-2 rounded-lg cursor-pointer transition-colors hover:bg-accent',
                      filters.types.includes(type.value) ? 'bg-accent' : ''
                    ]"
                  >
                    <Checkbox 
                      :model-value="filters.types.includes(type.value)"
                      @update:model-value="emit('update:type', type.value)" 
                    />
                    <span class="text-sm font-medium flex-1 truncate">{{ getTypeLabel(type.value) }}</span>
                    <Badge variant="outline" class="text-xs shrink-0">
                      {{ formatCount(type.count) }}
                    </Badge>
                  </label>
                </template>
                <div v-else class="text-sm text-muted-foreground p-2">
                  {{ $t('search.no_types_available') }}
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Building2, Filter, RotateCcw, Tag } from 'lucide-vue-next'

import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Checkbox } from '@/Components/ui/checkbox'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion'
import TenantFilter from './TenantFilter.vue'
import type { InstitutionFacet, InstitutionSearchFilters } from '@/Types/InstitutionSearchTypes'

interface Props {
  facets: InstitutionFacet[]
  filters: InstitutionSearchFilters
  isLoading?: boolean
  activeFilterCount?: number
  typeLabels?: Record<string, string>  // slug => localized title mapping
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  activeFilterCount: 0,
  typeLabels: () => ({})
})

const emit = defineEmits<{
  'update:tenant': [tenant: string]
  'update:type': [type: string]
  'clearFilters': []
}>()

const isMobileFiltersOpen = ref(false)

// Extract tenant facet for processing
const tenantFacet = computed(() => 
  props.facets.find(f => f.field === 'tenant_shortname')
)

// Extract type facet
const typeFacet = computed(() => 
  props.facets.find(f => f.field === 'type_slugs')
)

// Process tenant hierarchy for display - TenantFilter expects { main: Tenant[], padaliniai: Tenant[], pkp: Tenant[] }
const processedTenantHierarchy = computed(() => {
  const emptyHierarchy = { main: [], padaliniai: [], pkp: [] }
  
  if (!tenantFacet.value?.values) {
    return emptyHierarchy
  }
  
  const tenants = tenantFacet.value.values.map(tenant => ({
    shortname: tenant.value,
    name: tenant.value, // Use shortname as display name
    fullname: tenant.value,
    type: tenant.value === 'VU SA' ? 'main' : tenant.value.startsWith('VU SA ') ? 'padalinys' : 'other',
    count: tenant.count,
    isSelected: props.filters.tenants.includes(tenant.value)
  }))
  
  // Separate main organization (VU SA) from padaliniai (faculty units) and others
  const main = tenants.filter(t => t.shortname === 'VU SA')
  const padaliniai = tenants.filter(t => t.shortname !== 'VU SA' && t.shortname.startsWith('VU SA '))
  const pkp = tenants.filter(t => !t.shortname.startsWith('VU SA'))
  
  return { main, padaliniai, pkp }
})

// Get localized type label from slug
const getTypeLabel = (slug: string): string => {
  return props.typeLabels[slug] || slug
}

// Format count for display
const formatCount = (count: number): string => {
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}k`
  }
  return count.toString()
}
</script>
