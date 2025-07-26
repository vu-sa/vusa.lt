<template>
  <div v-if="typeResults && typeResults.results.length > 0">
          <div class="space-y-2">
            <!-- Section Header with total hits count -->
            <div :class="getSectionHeaderClasses(color)">
              {{ icon }} {{ title }} ({{ currentHits }})
            </div>

            <!-- Results -->
            <div class="space-y-3">
              <div v-for="item in typeResults.results" :key="`${item.id}-${type}`" 
                :class="getItemClasses(color)"
                @click="handleItemClick(item, $event)"
                :title="$t('search.click_to_open')"
                role="button"
                tabindex="0"
                @keydown="handleItemKeydown(item, $event)"
              >
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0">
                    <div :class="getIconClasses(color)">
                      <component :is="getIconComponent(type)" class="w-3 h-3" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-2">
                      <time class="text-xs text-muted-foreground">{{ formatDate(getItemDate(item)) }}</time>
                      <!-- Navigation indicator -->
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground opacity-60 group-hover:opacity-100 transition-opacity">
                        <path d="M7 17 17 7"/>
                        <path d="M7 7h10v10"/>
                      </svg>
                    </div>
                    <h3 class="font-medium text-base leading-tight mb-2 group-hover:text-primary transition-colors">
                      <AisHighlight attribute="title" :hit="item" />
                      <AisHighlight v-if="!item.title && item.name" attribute="name" :hit="item" />
                      <span v-if="!item.title && !item.name">{{ item.name || item.title || $t('search.untitled') }}</span>
                    </h3>
                    <div v-if="getItemContent(item)" class="text-sm text-muted-foreground leading-relaxed line-clamp-3 mb-1">
                      {{ stripHtml(getItemContent(item)) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load More Button - only show if there are items and more pages available -->
              <div v-if="!typeResults.isLastPage && typeResults.results.length > 0" class="px-3 py-2">
                <Button variant="ghost" size="sm" :class="getLoadMoreClasses(color)"
                  class="w-full justify-center text-xs" @click="typeResults.refineNext">
                  <ArrowRightIcon class="mr-1 w-3.5 h-3.5" />
                  {{ $t('Show more :type', { type: title.toLowerCase() }) }}
                </Button>
              </div>
            </div>
          </div>
  </div>
</template>

<script setup lang="ts">
import { computed, h, ref, watch, inject } from 'vue'
import { AisHighlight } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import { useSearchService, type SearchService } from '@/Composables/useSearchService'

// Icon components (simplified)
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

const ArrowRightIcon = () => h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round'
}, [
  h('path', { d: 'M5 12h14' }),
  h('path', { d: 'm12 5 7 7-7 7' })
])

interface Props {
  indexName: string
  title: string
  icon: string
  type: string
  color: string
  resultOrder?: 'relevance' | 'date' | 'type'
}

const props = withDefaults(defineProps<Props>(), {
  resultOrder: 'relevance'
})

const emit = defineEmits<{
  navigateToItem: [item: any]
  updateResultCount: [count: number]
  updateTotalHits: [totalHits: number]
}>()

// Use centralized search service - inject from parent or create new
const searchService = inject<SearchService>('searchService') || useSearchService()

// Get results for this content type from the search service
const typeResults = computed(() => {
  return searchService.getContentTypeResults(props.type)
})

// Current hits from the type results
const currentHits = computed(() => {
  return typeResults.value?.totalHits || 0
})

// Mobile detection
const isMobile = computed(() => {
  if (typeof window === 'undefined') return false
  return window.innerWidth < 1024
})

// Event handlers - simplified for direct navigation
const handleItemClick = (item: any, event: MouseEvent) => {
  const itemWithType = { ...item, type: props.type }
  emit('navigateToItem', itemWithType)
}

const handleItemKeydown = (item: any, event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    emit('navigateToItem', { ...item, type: props.type })
  }
}

// Watch for changes in type results and emit updates
watch(typeResults, (newTypeResults) => {
  if (newTypeResults) {
    emit('updateTotalHits', newTypeResults.totalHits)
    emit('updateResultCount', newTypeResults.results.length)
  } else {
    emit('updateTotalHits', 0)
    emit('updateResultCount', 0)
  }
}, { immediate: true })

const getIconComponent = (type: string) => {
  switch (type) {
    case 'news': return NewsIcon
    case 'pages': return PageIcon
    case 'documents': return DocumentIcon
    case 'calendar': return CalendarIcon
    default: return PageIcon
  }
}

const getSectionHeaderClasses = (color: string) => {
  const baseClasses = 'px-3 py-2 text-xs font-medium text-muted-foreground border-l-2'
  switch (color) {
    case 'blue': return `${baseClasses} bg-blue-50 dark:bg-blue-950/30 border-blue-500`
    case 'green': return `${baseClasses} bg-green-50 dark:bg-green-950/30 border-green-500`
    case 'purple': return `${baseClasses} bg-purple-50 dark:bg-purple-950/30 border-purple-500`
    case 'amber': return `${baseClasses} bg-amber-50 dark:bg-amber-950/30 border-amber-500`
    default: return `${baseClasses} bg-zinc-50 dark:bg-zinc-950/30 border-zinc-500`
  }
}

const getItemClasses = (color: string) => {
  const baseClasses = 'group mx-3 mb-4 p-5 rounded-lg border cursor-pointer hover:shadow-md transition-all duration-200 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-primary'

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

const getLoadMoreClasses = (color: string) => {
  switch (color) {
    case 'blue': return 'text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/30'
    case 'green': return 'text-green-600 hover:text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-950/30'
    case 'purple': return 'text-purple-600 hover:text-purple-700 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/30'
    case 'amber': return 'text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/30'
    default: return 'text-zinc-600 hover:text-zinc-700 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-950/30'
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
  switch (props.type) {
    case 'news': return item.publish_time
    case 'documents': return item.document_date
    case 'calendar': return item.date
    default: return item.created_at
  }
}

const getItemContent = (item: any) => {
  let content = ''
  switch (props.type) {
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
</script>

<style scoped>
/* Line clamping for content preview */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
