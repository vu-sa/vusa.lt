<template>
  <div class="unified-results">
    <!-- Properly implemented unified search with performance optimizations -->
    <AisIndex 
      v-for="contentType in enabledContentTypes" 
      :key="contentType.id"
      :index-name="contentType.indexName"
    >
      <AisConfigure :hits-per-page.camel="10" />
      
      <!-- Collect stats for this index -->
      <AisStats>
        <template #default="{ nbHits }">
          <StatsCollector :content-type-id="contentType.id" :nb-hits="nbHits" />
        </template>
      </AisStats>
      
      <AisInfiniteHits 
        :transform-items="(items) => transformItems(items, contentType)"
        :show-previous="false"
        class="hide-hits-display"
      >
        <template #default="{ items, isLastPage, refineNext }">
          <ResultsCollector 
            :content-type-id="contentType.id" 
            :items="items" 
            :content-type="contentType"
            :is-last-page="isLastPage"
            :refine-next="refineNext"
          />
        </template>
      </AisInfiniteHits>
    </AisIndex>

    <!-- Unified Results Display -->
    <div class="space-y-2 p-4" role="listbox" aria-label="Unified search results">
        <!-- Total results header -->
        <div v-if="totalHits > 0" class="px-3 py-2 text-xs font-medium text-muted-foreground border-l-2 bg-zinc-50 dark:bg-zinc-950/30 border-zinc-500 mb-4">
          🔍 {{ $t('Total Results') }}: {{ totalHits }} {{ $t('items found') }}
        </div>

        <!-- No results state -->
        <div v-if="unifiedResults.length === 0" class="text-center py-8">
          <div class="mx-auto w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400">
              <circle cx="11" cy="11" r="8"/>
              <path d="m21 21-4.35-4.35"/>
            </svg>
          </div>
          <p class="text-muted-foreground text-sm">{{ $t('No results found for your search') }}</p>
        </div>

        <!-- Unified result items -->
        <div 
          v-for="(item, index) in unifiedResults" 
          :key="`${item.type}-${item.id}-${index}`"
          :class="getUnifiedItemClasses(item, isSelected(item))"
          @click="() => $emit('selectItem', item)"
          @keydown.enter="() => $emit('selectItem', item)"
          @keydown.space.prevent="() => $emit('selectItem', item)"
          tabindex="0"
          role="option"
          :aria-selected="isSelected(item)"
        >
          <div class="flex items-start gap-3">
            <!-- Content type indicator -->
            <div class="flex-shrink-0">
              <div :class="getIconClasses(item.contentType.color)">
                <component :is="getIconComponent(item.type)" class="w-3 h-3" />
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <!-- Header with date and type -->
              <div class="flex items-center gap-2 mb-1">
                <Badge :class="getTypeBadgeClasses(item.contentType.color)" class="text-xs">
                  {{ item.contentType.icon }} {{ $t(item.contentType.name) }}
                </Badge>
                <time class="text-xs text-muted-foreground">
                  {{ formatDate(getItemDate(item)) }}
                </time>
                <div v-if="item._rankingInfo?.rank" class="text-xs text-muted-foreground ml-auto">
                  #{{ item._rankingInfo.rank }}
                </div>
              </div>

              <!-- Title -->
              <h3 class="font-medium text-sm leading-tight mb-1">
                <AisHighlight attribute="title" :hit="item" />
                <AisHighlight v-if="!item.title && item.name" attribute="name" :hit="item" />
                <span v-if="!item.title && !item.name">{{ item.name || item.title || $t('Untitled') }}</span>
              </h3>

              <!-- Content preview -->
              <div v-if="getItemContent(item)" class="text-xs text-muted-foreground leading-relaxed line-clamp-2">
                {{ stripHtml(getItemContent(item)) }}
              </div>
            </div>

            <!-- Action indicator -->
            <div class="flex-shrink-0">
              <div class="w-4 h-4 rounded-full border-2 border-zinc-300 dark:border-zinc-600 transition-colors"
                   :class="{ 'border-primary bg-primary': isSelected(item) }">
                <div v-if="isSelected(item)" class="w-2 h-2 bg-white rounded-full m-0.5"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Load more indicator -->
        <div v-if="hasMoreResults" class="text-center py-4">
          <Button variant="ghost" size="sm" @click="loadMoreResults" :disabled="isLoadingMore">
            <div v-if="isLoadingMore" class="animate-spin rounded-full h-4 w-4 border-2 border-primary border-t-transparent mr-2"></div>
            {{ isLoadingMore ? $t('Loading...') : $t('Load more results') }}
          </Button>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, h, nextTick, defineComponent, onUnmounted } from 'vue'
import { AisIndex, AisInfiniteHits, AisHighlight, AisConfigure, AisStats } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import type { ContentType } from '@/Composables/useSearchController'

// Icon components (reused from SearchResultSection)
const NewsIcon = () => h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round'
}, [
  h('path', { d: 'M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2' }),
  h('path', { d: 'M18 14h-8' }),
  h('path', { d: 'M15 18h-5' }),
  h('path', { d: 'M10 6h8v4h-8V6Z' })
])

const PageIcon = () => h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round'
}, [
  h('path', { d: 'M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z' }),
  h('path', { d: 'M14 2v4a2 2 0 0 0 2 2h4' })
])

const DocumentIcon = () => h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round'
}, [
  h('path', { d: 'M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z' }),
  h('path', { d: 'M14 2v4a2 2 0 0 0 2 2h4' }),
  h('path', { d: 'M10 9l2 2 4-4' })
])

const CalendarIcon = () => h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round'
}, [
  h('path', { d: 'M8 2v4' }),
  h('path', { d: 'M16 2v4' }),
  h('rect', { width: '18', height: '18', x: '3', y: '4', rx: '2' }),
  h('path', { d: 'M3 10h18' })
])

interface Props {
  enabledContentTypes: ContentType[]
  selectedItem: any
  resultOrder: 'relevance' | 'date' | 'type'
  maxResults?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxResults: 50
})

const emit = defineEmits<{
  selectItem: [item: any]
  updateResultCount: [count: number]
  updateTotalHits: [contentTypeId: string, totalHits: number]
}>()

// Reactive state for storing results from multiple indices - simplified approach
const resultsByType = ref<Record<string, any[]>>({})
const statsByType = ref<Record<string, number>>({})
const paginationByType = ref<Record<string, { isLastPage: boolean, refineNext: () => void }>>({})
const isLoadingMore = ref(false)

// Individual debounce timers per content type to prevent conflicts
const debounceTimers = ref<Record<string, ReturnType<typeof setTimeout> | null>>({})

// Performance-optimized helper components to prevent template function calls
const StatsCollector = defineComponent({
  props: ['contentTypeId', 'nbHits'],
  setup(props) {
    watch(() => props.nbHits, (newHits) => {
      // Clear existing timer for this content type
      if (debounceTimers.value[props.contentTypeId]) {
        clearTimeout(debounceTimers.value[props.contentTypeId]!)
      }
      
      // Set new debounced update
      debounceTimers.value[props.contentTypeId] = setTimeout(() => {
        statsByType.value[props.contentTypeId] = newHits
        debounceTimers.value[props.contentTypeId] = null
      }, 100)
    }, { immediate: true })
    return () => null
  }
})

const ResultsCollector = defineComponent({
  props: ['contentTypeId', 'items', 'contentType', 'isLastPage', 'refineNext'],
  setup(props) {
    watch(() => props.items, (newItems) => {
      // Clear existing timer for this content type
      const timerKey = `results_${props.contentTypeId}`
      if (debounceTimers.value[timerKey]) {
        clearTimeout(debounceTimers.value[timerKey]!)
      }
      
      // Set new debounced update
      debounceTimers.value[timerKey] = setTimeout(() => {
        resultsByType.value[props.contentTypeId] = newItems.map((item: any) => ({
          ...item,
          contentType: props.contentType
        }))
        debounceTimers.value[timerKey] = null
      }, 100)
    }, { immediate: true, deep: true })

    // Store pagination info
    watch(() => [props.isLastPage, props.refineNext], ([isLastPage, refineNext]) => {
      // Store pagination state for this content type
      paginationByType.value[props.contentTypeId] = {
        isLastPage,
        refineNext
      }
    }, { immediate: true })
    
    return () => null
  }
})

// Transform items efficiently without causing re-renders
const transformItems = (items: any[], contentType: ContentType) => {
  return items.map(item => ({
    ...item,
    title: item.title || item.name || '',
    type: contentType.id,
    contentType: contentType
  }))
}

// Unified results computed property - removed emit side effect
const unifiedResults = computed(() => {
  const allResults: any[] = []
  
  // Collect results from all enabled content types
  props.enabledContentTypes.forEach(contentType => {
    const typeResults = resultsByType.value[contentType.id] || []
    allResults.push(...typeResults)
  })

  // Sort based on result order preference
  let sortedResults = [...allResults]

  switch (props.resultOrder) {
    case 'date':
      sortedResults.sort((a, b) => {
        const aDate = getItemDate(a)
        const bDate = getItemDate(b)
        if (!aDate && !bDate) return 0
        if (!aDate) return 1
        if (!bDate) return -1
        
        const aTime = typeof aDate === 'number' ? aDate : new Date(aDate).getTime()
        const bTime = typeof bDate === 'number' ? bDate : new Date(bDate).getTime()
        
        return bTime - aTime // Most recent first
      })
      break
      
    case 'type':
      sortedResults.sort((a, b) => {
        const aOrder = a.contentType?.order || 999
        const bOrder = b.contentType?.order || 999
        if (aOrder !== bOrder) return aOrder - bOrder
        return 0
      })
      break
      
    case 'relevance':
    default:
      sortedResults.sort((a, b) => {
        const aScore = a._score || 0
        const bScore = b._score || 0
        return bScore - aScore
      })
      break
  }

  // Limit results for display
  return sortedResults.slice(0, props.maxResults)
})

// Computed total hits across all indices
const totalHits = computed(() => {
  return Object.values(statsByType.value).reduce((sum, hits) => sum + hits, 0)
})

// Separate watcher for emitting result count to prevent computed side effects
watch(statsByType, (newStats) => {
  // Emit total combined count for the unified view stats display
  const totalCount = Object.values(newStats).reduce((sum, hits) => sum + hits, 0)
  emit('updateResultCount', totalCount)
  
  // Also emit individual content type totals for search controller
  props.enabledContentTypes.forEach(contentType => {
    const hits = newStats[contentType.id] || 0
    emit('updateTotalHits', contentType.id, hits)
  })
}, { deep: true, immediate: true })

// Helper functions (kept for potential future implementation)
const isSelected = (item: any) => {
  return props.selectedItem?.id === item.id && props.selectedItem?.type === item.type
}

const getIconComponent = (type: string) => {
  switch (type) {
    case 'news': return NewsIcon
    case 'pages': return PageIcon
    case 'documents': return DocumentIcon
    case 'calendar': return CalendarIcon
    default: return PageIcon
  }
}

const getUnifiedItemClasses = (item: any, isSelected: boolean) => {
  const baseClasses = 'mx-2 mb-2 p-3 rounded-lg border cursor-pointer hover:shadow-sm transition-all duration-200 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2'
  
  if (isSelected) {
    const color = item.contentType?.color || 'zinc'
    switch (color) {
      case 'blue':
        return `${baseClasses} border-blue-500 dark:border-blue-400 shadow-md bg-blue-50/50 dark:bg-blue-950/20`
      case 'green':
        return `${baseClasses} border-green-500 dark:border-green-400 shadow-md bg-green-50/50 dark:bg-green-950/20`
      case 'purple':
        return `${baseClasses} border-purple-500 dark:border-purple-400 shadow-md bg-purple-50/50 dark:bg-purple-950/20`
      case 'amber':
        return `${baseClasses} border-amber-500 dark:border-amber-400 shadow-md bg-amber-50/50 dark:bg-amber-950/20`
      default:
        return `${baseClasses} border-zinc-500 dark:border-zinc-400 shadow-md bg-zinc-50/50 dark:bg-zinc-950/20`
    }
  } else {
    return `${baseClasses} border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600`
  }
}

const getIconClasses = (color: string) => {
  const baseClasses = 'w-6 h-6 rounded flex items-center justify-center'
  switch (color) {
    case 'blue': return `${baseClasses} bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400`
    case 'green': return `${baseClasses} bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400`
    case 'purple': return `${baseClasses} bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400`
    case 'amber': return `${baseClasses} bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400`
    default: return `${baseClasses} bg-zinc-100 dark:bg-zinc-900/50 text-zinc-600 dark:text-zinc-400`
  }
}

const getTypeBadgeClasses = (color: string) => {
  const baseClasses = 'text-xs border'
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

const formatDate = (dateValue: string | number | undefined) => {
  if (!dateValue) return ''
  
  try {
    let date: Date
    
    if (typeof dateValue === 'number') {
      const timestamp = dateValue < 10000000000 ? dateValue * 1000 : dateValue
      date = new Date(timestamp)
    } else {
      date = new Date(dateValue)
    }
    
    if (isNaN(date.getTime())) {
      return String(dateValue)
    }
    
    return format(date, 'MMM dd, yyyy')
  } catch {
    return String(dateValue)
  }
}

const stripHtml = (html: string): string => {
  if (!html) return ''
  const tmp = document.createElement('div')
  tmp.innerHTML = html
  return tmp.textContent || tmp.innerText || ''
}

const getItemDate = (item: any) => {
  switch (item.type) {
    case 'news': return item.publish_time
    case 'documents': return item.document_date
    case 'calendar': return item.date
    default: return item.created_at
  }
}

const getItemContent = (item: any) => {
  switch (item.type) {
    case 'news': return item.short
    case 'documents': return item.summary
    case 'pages': return item.content?.slice(0, 120) + '...'
    case 'calendar': return item.description
    default: return item.summary || item.content || item.description
  }
}

// Computed to check if any content type has more results
const hasMoreResults = computed(() => {
  return Object.values(paginationByType.value).some(pagination => !pagination.isLastPage)
})

const loadMoreResults = () => {
  if (isLoadingMore.value) return
  
  isLoadingMore.value = true
  
  // Find the content type with the fewest results to load more from
  // This helps maintain balance across content types
  let minResults = Infinity
  let targetContentType = null
  
  props.enabledContentTypes.forEach(contentType => {
    const pagination = paginationByType.value[contentType.id]
    const results = resultsByType.value[contentType.id] || []
    
    if (pagination && !pagination.isLastPage && results.length < minResults) {
      minResults = results.length
      targetContentType = contentType.id
    }
  })
  
  if (targetContentType && paginationByType.value[targetContentType]) {
    // Load more results for the selected content type
    paginationByType.value[targetContentType].refineNext()
  }
  
  // Reset loading state after a short delay
  setTimeout(() => {
    isLoadingMore.value = false
  }, 500)
}

// Watch for changes in result order to re-sort
watch(() => props.resultOrder, () => {
  // The computed property will automatically re-sort when this changes
}, { immediate: true })

// Cleanup timers and reset state when content types change
watch(() => props.enabledContentTypes, (newTypes, oldTypes) => {
  // Clear all timers
  Object.values(debounceTimers.value).forEach(timer => {
    if (timer) clearTimeout(timer)
  })
  debounceTimers.value = {}
  
  // Reset state for content types that are no longer enabled
  if (oldTypes) {
    const newTypeIds = new Set(newTypes.map(t => t.id))
    Object.keys(resultsByType.value).forEach(typeId => {
      if (!newTypeIds.has(typeId)) {
        delete resultsByType.value[typeId]
        delete statsByType.value[typeId]
        delete paginationByType.value[typeId]
      }
    })
  }
}, { immediate: true })

// Cleanup on unmount
onUnmounted(() => {
  Object.values(debounceTimers.value).forEach(timer => {
    if (timer) clearTimeout(timer)
  })
})
</script>

<style scoped>
/* Hide the search hit displays since we're just collecting results */
.hide-hits-display :deep(.ais-InfiniteHits) {
  display: none;
}

/* Smooth transitions for unified results */
.unified-results {
  --transition-duration: 200ms;
}

/* Focus styles for accessibility */
.unified-results [role="option"]:focus {
  outline: 2px solid var(--ring);
  outline-offset: 2px;
}

/* Line clamping for content preview */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>