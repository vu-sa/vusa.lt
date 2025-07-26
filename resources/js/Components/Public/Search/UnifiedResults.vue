<template>
  <div class="unified-results">
    <!-- Results collection will happen via the search service, no multiple indexes needed -->

    <!-- Unified Results Display -->
    <div class="space-y-3 p-6" role="listbox" aria-label="Unified search results">
        <!-- Total results header -->
        <div v-if="totalHits > 0" class="px-3 py-2 text-xs font-medium text-muted-foreground border-l-2 bg-zinc-50 dark:bg-zinc-950/30 border-zinc-500 mb-4">
          🔍 {{ $t('search.total_results') }}: {{ totalHits }} {{ $t('search.items_found') }}
        </div>

        <!-- No results state -->
                <div v-if="totalHits === 0" class="text-center py-8">
          <p class="text-muted-foreground text-sm">{{ $t('search.no_results_found_for_your_search') }}</p>
        </div>

        <!-- Unified result items -->
        <div 
          v-for="(item, index) in unifiedResults" 
          :key="`${item.type}-${item.id}-${index}`"
          :class="getUnifiedItemClasses(item)"
          @click="(event) => handleItemClick(item, event)"
          @keydown.enter="() => handleItemNavigation(item)"
          @keydown.space.prevent="() => handleItemNavigation(item)"
          tabindex="0"
          role="option"
          :title="$t('search.click_to_open')"
          class="group cursor-pointer"
        >
          <div class="flex items-start gap-4">
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
              <h3 class="font-medium text-base leading-tight mb-2 group-hover:text-primary transition-colors">
                <AisHighlight attribute="title" :hit="item" />
                <AisHighlight v-if="!item.title && item.name" attribute="name" :hit="item" />
                <span v-if="!item.title && !item.name">{{ item.name || item.title || $t('search.untitled') }}</span>
              </h3>

              <!-- Content preview -->
              <div v-if="getItemContent(item)" class="text-sm text-muted-foreground leading-relaxed line-clamp-3 mb-1">
                {{ stripHtml(getItemContent(item)) }}
              </div>
            </div>

            <!-- Action indicator -->
            <div class="flex-shrink-0 flex items-center">
              <!-- Navigation indicator -->
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground opacity-60 group-hover:opacity-100 transition-opacity">
                <path d="M7 17 17 7"/>
                <path d="M7 7h10v10"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Load more indicator -->
        <div v-if="hasMoreResults" class="text-center py-4">
          <Button variant="ghost" size="sm" @click="loadMoreResults" :disabled="isLoadingMore">
            <div v-if="isLoadingMore" class="animate-spin rounded-full h-4 w-4 border-2 border-primary border-t-transparent mr-2"></div>
            {{ isLoadingMore ? $t('search.loading') : $t('search.load_more_results') }}
          </Button>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, h, nextTick, onMounted, inject } from 'vue'
import { AisHighlight } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import type { ContentType } from '@/Composables/useSearchController'
import { useSearchService, type SearchService } from '@/Composables/useSearchService'

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
  resultOrder: 'relevance' | 'date' | 'type'
  maxResults?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxResults: 50
})

const emit = defineEmits<{
  navigateToItem: [item: any]
  updateResultCount: [count: number]
  updateTotalHits: [contentTypeId: string, totalHits: number]
}>()

// Use centralized search service - inject from parent or create new
const searchService = inject<SearchService>('searchService') || useSearchService()
const isLoadingMore = ref(false)

// Get unified results from search service
const unifiedResults = computed(() => {
  return searchService.getUnifiedResults(props.enabledContentTypes, props.resultOrder, props.maxResults)
})

// Get total hits from search service
const totalHits = computed(() => {
  return searchService.totalHits.value
})

// Watch for results changes and emit updates
watch(() => searchService.resultsByType.value, (newResults) => {
  let totalCount = 0
  
  // Emit individual content type totals
  props.enabledContentTypes.forEach(contentType => {
    const typeResult = newResults[contentType.id]
    const hits = typeResult?.totalHits || 0
    totalCount += hits
    emit('updateTotalHits', contentType.id, hits)
  })
  
  // Emit total combined count
  emit('updateResultCount', totalCount)
}, { deep: true, immediate: true })

// Mobile detection
const isMobile = computed(() => {
  if (typeof window === 'undefined') return false
  return window.innerWidth < 1024
})

// Event handlers for direct navigation
const handleItemClick = (item: any, event: MouseEvent) => {
  emit('navigateToItem', item)
}

const handleItemNavigation = (item: any) => {
  emit('navigateToItem', item)
}

// Helper functions

const getIconComponent = (type: string) => {
  switch (type) {
    case 'news': return NewsIcon
    case 'pages': return PageIcon
    case 'documents': return DocumentIcon
    case 'calendar': return CalendarIcon
    default: return PageIcon
  }
}

const getUnifiedItemClasses = (item: any) => {
  const baseClasses = 'mx-3 mb-4 p-5 rounded-lg border cursor-pointer hover:shadow-md transition-all duration-200 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2'
  
  const color = item.contentType?.color || 'zinc'
  let colorClasses = ''
  
  switch (color) {
    case 'blue':
      colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50/50 dark:hover:bg-blue-950/20'
      break
    case 'green':
      colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-green-300 dark:hover:border-green-600 hover:bg-green-50/50 dark:hover:bg-green-950/20'
      break
    case 'purple':
      colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-purple-300 dark:hover:border-purple-600 hover:bg-purple-50/50 dark:hover:bg-purple-950/20'
      break
    case 'amber':
      colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-amber-300 dark:hover:border-amber-600 hover:bg-amber-50/50 dark:hover:bg-amber-950/20'
      break
    default:
      colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800'
  }
  
  return `${baseClasses} ${colorClasses}`
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
  let content = ''
  switch (item.type) {
    case 'news': 
      content = item.short || item.summary || item.content
      break
    case 'documents': 
      content = item.summary || item.content
      break
    case 'pages': 
      content = item.content || item.summary
      break
    case 'calendar': 
      content = item.description || item.summary
      break
    default: 
      content = item.summary || item.content || item.description
  }
  
  // Expand content length for better information display
  if (content && content.length > 200) {
    return content.slice(0, 200) + '...'
  }
  return content
}

// Check if more results are available from search service
const hasMoreResults = computed(() => {
  return searchService.hasMoreResults.value
})

const loadMoreResults = () => {
  if (isLoadingMore.value) return
  
  isLoadingMore.value = true
  
  // Use search service to load more results
  searchService.loadMoreResults()
  
  // Reset loading state after a short delay
  setTimeout(() => {
    isLoadingMore.value = false
  }, 500)
}

// Watch for changes in content types to clear disabled results
watch(() => props.enabledContentTypes, (newTypes, oldTypes) => {
  if (oldTypes) {
    const newTypeIds = new Set(newTypes.map(t => t.id))
    oldTypes.forEach(oldType => {
      if (!newTypeIds.has(oldType.id)) {
        searchService.clearContentTypeResults(oldType.id)
      }
    })
  }
}, { immediate: true })
</script>

<style scoped>
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
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>