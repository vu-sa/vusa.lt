<template>
  <div class="unified-results">
    <!-- Unified Results Display -->
    <div class="space-y-3 p-6" role="listbox" aria-label="Unified search results">
        <!-- Total results header -->
        <div v-if="totalHits > 0" class="px-3 py-2 text-xs font-medium text-muted-foreground border-l-2 bg-red-50 dark:bg-red-950/30 border-vusa-red mb-4">
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
              <div :class="getIconClasses()">
                <component :is="getIconComponent(item.type)" class="w-3 h-3" />
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <!-- Header with date and type -->
              <div class="flex items-center gap-2 mb-1">
                <Badge :class="getTypeBadgeClasses()" class="text-xs">
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
                        <!-- Action indicator -->
            <div class="flex-shrink-0 flex items-center">
              <!-- Navigation indicator -->
              <IconArrowRight class="w-3 h-3 text-muted-foreground opacity-60 group-hover:opacity-100 transition-opacity" />
            </div>
          </div>
        </div>

        <!-- Load more indicator -->
        <div v-if="hasMoreResults" class="text-center py-4">
          <Button variant="ghost" size="sm" @click="loadMoreResults" :disabled="isLoadingMore">
            <IconSpinner v-if="isLoadingMore" class="w-4 h-4 animate-spin mr-2" />
            {{ isLoadingMore ? $t('search.loading') : $t('search.load_more_results') }}
          </Button>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, inject } from 'vue'
import { AisHighlight } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import type { ContentType } from '@/Composables/useSearchController'
import { useSearchService, type SearchService } from '@/Composables/useSearchService'

// Import icons using the ~icons pattern like DutyForm.vue and SearchResultSection.vue
import IconNews from '~icons/fluent/news24-regular'
import IconPage from '~icons/fluent/document24-regular'
import IconDocument from '~icons/fluent/document-checkmark24-regular'
import IconCalendar from '~icons/fluent/calendar24-regular'
import IconArrowUpRight from '~icons/fluent/arrow-up-right20-regular'
import IconSpinner from '~icons/fluent/spinner-ios20-filled'

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
    case 'news': return IconNews
    case 'pages': return IconPage
    case 'documents': return IconDocument
    case 'calendar': return IconCalendar
    default: return IconPage
  }
}

const getUnifiedItemClasses = (item: any) => {
  return 'mx-3 mb-4 p-5 rounded-lg border cursor-pointer hover:shadow-md transition-all duration-200 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-vusa-red focus:ring-offset-2 border-zinc-200 dark:border-zinc-700 hover:border-vusa-red/50 dark:hover:border-vusa-red/50 hover:bg-red-50/30 dark:hover:bg-red-950/10'
}

const getIconClasses = () => {
  return 'w-6 h-6 rounded flex items-center justify-center bg-red-100 dark:bg-red-900/50 text-vusa-red dark:text-red-400'
}

const getTypeBadgeClasses = () => {
  return 'text-xs border bg-red-100 text-vusa-red border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'
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
</style>