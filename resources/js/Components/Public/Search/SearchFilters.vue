<template>
  <div class="search-filters space-y-4">
    <!-- Content Type Filters -->
    <div class="content-type-filters">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-muted-foreground">
          {{ $t('search.search_in') }}
        </h3>
        <div class="flex items-center gap-2">
          <!-- Select All/None Toggle -->
          <Button variant="ghost" size="sm" class="text-xs h-6 px-2" @click="toggleAllTypes">
            {{ allTypesSelected ? $t('search.deselect_all') : $t('search.select_all') }}
          </Button>
        </div>
      </div>

      <!-- Content Type Checkboxes -->
      <div class="grid grid-cols-2 gap-2">
        <label v-for="contentType in contentTypes" :key="contentType.id" :class="getContentTypeClasses(contentType)"
          class="flex items-center gap-2 p-2 rounded-md border cursor-pointer transition-all duration-200 hover:border-zinc-300 dark:hover:border-zinc-600">
          <Checkbox :model-value="contentType.enabled" :class="getCheckboxClasses()"
            @update:model-value="() => toggleContentType(contentType.id)" />
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <span class="text-sm">{{ contentType.icon }}</span>
            <span class="text-sm font-medium truncate">
              {{ $t(contentType.name) }}
            </span>
            <!-- Result count badge -->
            <Badge v-if="getContentTypeResultCount(contentType.id) !== undefined"
              :class="getBadgeClasses(contentType.enabled)" class="text-xs ml-auto">
              {{ getContentTypeResultCount(contentType.id) }}
            </Badge>
          </div>
        </label>
      </div>
    </div>

    <!-- Search Options -->
    <div class="search-options space-y-3">
      <!-- Display Options -->
      <div>
        <h4 class="text-sm font-medium text-muted-foreground mb-2">
          {{ $t('search.display') }}
        </h4>
        <label class="flex items-center gap-2 cursor-pointer">
          <Checkbox :model-value="groupResults" @update:model-value="toggleGroupResults" />
          <span class="text-sm">{{ $t('search.group_results_by_type') }}</span>
        </label>
      </div>

      <!-- Result Ordering - Only show when NOT grouping results -->
      <div v-if="!groupResults">
        <h4 class="text-sm font-medium text-muted-foreground mb-2">
          {{ $t('search.sort_results_by') }}
        </h4>
        <div class="flex gap-1">
          <Button v-for="option in sortOptions" :key="option.value"
            :variant="resultOrder === option.value ? 'default' : 'ghost'" size="sm" class="text-xs h-7 px-2"
            @click="setResultOrder(option.value)">
            {{ $t(option.label) }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Recent Searches -->
    <div v-if="recentSearches.length > 0" class="recent-searches">
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium text-muted-foreground">
          {{ $t('search.recent_searches') }}
        </h4>
        <Button variant="ghost" size="sm" class="text-xs h-6 px-2" @click="clearRecentSearches">
          {{ $t('search.clear') }}
        </Button>
      </div>
      <div class="flex flex-wrap gap-1">
        <Button v-for="search in recentSearches.slice(0, 5)" :key="search" variant="outline" size="sm"
          class="text-xs h-6 px-2" @click="$emit('selectRecentSearch', search)">
          {{ search }}
        </Button>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions pt-2 border-t border-zinc-200 dark:border-zinc-700">
      <div class="flex items-center justify-between">
        <span class="text-xs text-muted-foreground">
          {{ $t('search.quick_actions') }}
        </span>
        <Button variant="ghost" size="sm" class="text-xs h-6 px-2" @click="resetToDefaults">
          {{ $t('search.reset_filters') }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Button } from '@/Components/ui/button'
import { Checkbox } from '@/Components/ui/checkbox'
import { Badge } from '@/Components/ui/badge'
import type { ContentType } from '@/Composables/useSearchController'

interface Props {
  contentTypes: ContentType[]
  resultOrder: 'relevance' | 'date' | 'type'
  groupResults: boolean
  resultCounts: Record<string, number>
  recentSearches: string[]
}

interface Emits {
  (e: 'toggleContentType', typeId: string): void
  (e: 'setResultOrder', order: 'relevance' | 'date' | 'type'): void
  (e: 'toggleGroupResults'): void
  (e: 'clearRecentSearches'): void
  (e: 'resetToDefaults'): void
  (e: 'selectRecentSearch', search: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Sort options configuration
const sortOptions = [
  { value: 'relevance' as const, label: 'Relevance' },
  { value: 'date' as const, label: 'Date' },
  { value: 'type' as const, label: 'Type' }
]

// Computed properties
const allTypesSelected = computed(() =>
  props.contentTypes.every(type => type.enabled)
)

const enabledTypesCount = computed(() =>
  props.contentTypes.filter(type => type.enabled).length
)

// Actions
const toggleContentType = (typeId: string) => {
  emit('toggleContentType', typeId)
}

const setResultOrder = (order: 'relevance' | 'date' | 'type') => {
  emit('setResultOrder', order)
}

const toggleGroupResults = () => {
  emit('toggleGroupResults')
}

const clearRecentSearches = () => {
  emit('clearRecentSearches')
}

const resetToDefaults = () => {
  emit('resetToDefaults')
}

// Helper to get the appropriate result count for a content type
const getContentTypeResultCount = (contentTypeId: string) => {
  // First try to get total hits, fall back to displayed results
  const totalKey = `${contentTypeId}_total`
  const displayedKey = contentTypeId

  return props.resultCounts[totalKey] !== undefined
    ? props.resultCounts[totalKey]
    : props.resultCounts[displayedKey]
}

const toggleAllTypes = () => {
  // If all are selected, deselect all. Otherwise, select all.
  const shouldSelectAll = !allTypesSelected.value

  props.contentTypes.forEach(type => {
    if (type.enabled !== shouldSelectAll) {
      emit('toggleContentType', type.id)
    }
  })
}

// Styling functions
const getContentTypeClasses = (contentType: ContentType) => {
  const baseClasses = 'transition-all duration-200'

  if (contentType.enabled) {
    return `${baseClasses} bg-red-50 dark:bg-red-950/30 border-vusa-red dark:border-vusa-red/50`
  } else {
    return `${baseClasses} bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 opacity-60`
  }
}

const getCheckboxClasses = () => {
  return 'data-[state=checked]:bg-vusa-red data-[state=checked]:border-vusa-red'
}

const getBadgeClasses = (enabled: boolean) => {
  const baseClasses = 'text-xs border'

  if (!enabled) {
    return `${baseClasses} bg-zinc-100 text-zinc-500 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700`
  }

  return `${baseClasses} bg-red-100 text-vusa-red border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800`
}
</script>

<style scoped>
.search-filters {
  /* Ensure consistent spacing and smooth animations */
  --transition-duration: 200ms;
}

.content-type-filters label:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dark .content-type-filters label:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Custom focus styles for better accessibility */
.content-type-filters label:focus-within {
  outline: 2px solid var(--ring);
  outline-offset: 2px;
}
</style>
