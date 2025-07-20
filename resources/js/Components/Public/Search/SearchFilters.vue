<template>
  <div class="search-filters space-y-4">
    <!-- Content Type Filters -->
    <div class="content-type-filters">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-muted-foreground">
          {{ $t('Search in') }}
        </h3>
        <div class="flex items-center gap-2">
          <!-- Select All/None Toggle -->
          <Button
            variant="ghost"
            size="sm"
            class="text-xs h-6 px-2"
            @click="toggleAllTypes"
          >
            {{ allTypesSelected ? $t('Deselect all') : $t('Select all') }}
          </Button>
        </div>
      </div>
      
      <!-- Content Type Checkboxes -->
      <div class="grid grid-cols-2 gap-2">
        <label
          v-for="contentType in contentTypes"
          :key="contentType.id"
          :class="getContentTypeClasses(contentType)"
          class="flex items-center gap-2 p-2 rounded-md border cursor-pointer transition-all duration-200 hover:border-zinc-300 dark:hover:border-zinc-600"
        >
          <Checkbox
            :model-value="contentType.enabled"
            @update:model-value="() => toggleContentType(contentType.id)"
            :class="getCheckboxClasses(contentType.color)"
          />
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <span class="text-sm">{{ contentType.icon }}</span>
            <span class="text-sm font-medium truncate">
              {{ $t(contentType.name) }}
            </span>
            <!-- Result count badge -->
            <Badge
              v-if="getContentTypeResultCount(contentType.id) !== undefined"
              :class="getBadgeClasses(contentType.color, contentType.enabled)"
              class="text-xs ml-auto"
            >
              {{ getContentTypeResultCount(contentType.id) }}
            </Badge>
          </div>
        </label>
      </div>
    </div>

    <!-- Search Options -->
    <div class="search-options space-y-3">
      <!-- Result Ordering -->
      <div>
        <h4 class="text-sm font-medium text-muted-foreground mb-2">
          {{ $t('Sort results by') }}
        </h4>
        <div class="flex gap-1">
          <Button
            v-for="option in sortOptions"
            :key="option.value"
            :variant="resultOrder === option.value ? 'default' : 'ghost'"
            size="sm"
            class="text-xs h-7 px-2"
            @click="setResultOrder(option.value)"
          >
            {{ $t(option.label) }}
          </Button>
        </div>
      </div>

      <!-- Display Options -->
      <div>
        <h4 class="text-sm font-medium text-muted-foreground mb-2">
          {{ $t('Display') }}
        </h4>
        <label class="flex items-center gap-2 cursor-pointer">
          <Checkbox
            :model-value="groupResults"
            @update:model-value="toggleGroupResults"
          />
          <span class="text-sm">{{ $t('Group results by content type') }}</span>
        </label>
      </div>
    </div>

    <!-- Recent Searches -->
    <div v-if="recentSearches.length > 0" class="recent-searches">
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium text-muted-foreground">
          {{ $t('Recent searches') }}
        </h4>
        <Button
          variant="ghost"
          size="sm"
          class="text-xs h-6 px-2"
          @click="clearRecentSearches"
        >
          {{ $t('Clear') }}
        </Button>
      </div>
      <div class="flex flex-wrap gap-1">
        <Button
          v-for="search in recentSearches.slice(0, 5)"
          :key="search"
          variant="outline"
          size="sm"
          class="text-xs h-6 px-2"
          @click="$emit('selectRecentSearch', search)"
        >
          {{ search }}
        </Button>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions pt-2 border-t border-zinc-200 dark:border-zinc-700">
      <div class="flex items-center justify-between">
        <span class="text-xs text-muted-foreground">
          {{ $t('Quick actions') }}
        </span>
        <Button
          variant="ghost"
          size="sm"
          class="text-xs h-6 px-2"
          @click="resetToDefaults"
        >
          {{ $t('Reset filters') }}
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
    switch (contentType.color) {
      case 'blue':
        return `${baseClasses} bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800`
      case 'green':
        return `${baseClasses} bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-800`
      case 'purple':
        return `${baseClasses} bg-purple-50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800`
      case 'amber':
        return `${baseClasses} bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800`
      default:
        return `${baseClasses} bg-zinc-50 dark:bg-zinc-950/30 border-zinc-200 dark:border-zinc-800`
    }
  } else {
    return `${baseClasses} bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 opacity-60`
  }
}

const getCheckboxClasses = (color: string) => {
  switch (color) {
    case 'blue':
      return 'data-[state=checked]:bg-blue-600 data-[state=checked]:border-blue-600'
    case 'green':
      return 'data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600'
    case 'purple':
      return 'data-[state=checked]:bg-purple-600 data-[state=checked]:border-purple-600'
    case 'amber':
      return 'data-[state=checked]:bg-amber-600 data-[state=checked]:border-amber-600'
    default:
      return 'data-[state=checked]:bg-zinc-600 data-[state=checked]:border-zinc-600'
  }
}

const getBadgeClasses = (color: string, enabled: boolean) => {
  const baseClasses = 'text-xs border'
  
  if (!enabled) {
    return `${baseClasses} bg-zinc-100 text-zinc-500 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700`
  }
  
  switch (color) {
    case 'blue':
      return `${baseClasses} bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800`
    case 'green':
      return `${baseClasses} bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800`
    case 'purple':
      return `${baseClasses} bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800`
    case 'amber':
      return `${baseClasses} bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800`
    default:
      return `${baseClasses} bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700`
  }
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