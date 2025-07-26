<template>
  <div v-if="typeResults && typeResults.results.length > 0">
    <div class="space-y-2">
      <!-- Section Header with total hits count -->
      <div :class="getSectionHeaderClasses()">
        {{ icon }} {{ title }} ({{ currentHits }})
      </div>

      <!-- Results -->
      <div class="space-y-3">
        <div v-for="item in typeResults.results" :key="`${item.id}-${type}`" :class="getItemClasses()"
          :title="$t('search.click_to_open')" role="button" tabindex="0" @click="handleItemClick(item, $event)"
          @keydown="handleItemKeydown(item, $event)">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <div :class="getIconClasses()">
                <component :is="getIconComponent(type)" class="w-3 h-3" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2 mb-2">
                <time class="text-xs text-muted-foreground">{{ formatDate(getItemDate(item)) }}</time>
                <!-- Navigation indicator -->
                <IconArrowRight class="w-3 h-3 text-muted-foreground opacity-60 group-hover:opacity-100 transition-opacity" />
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
          <Button variant="ghost" size="sm" :class="getLoadMoreClasses()" class="w-full justify-center text-xs"
            @click="typeResults.refineNext">
            <IconArrowRight class="mr-1 w-3.5 h-3.5" />
            {{ $t('search.show_more_type', { type: title.toLowerCase() }) }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, inject } from 'vue'
import { AisHighlight } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import { useSearchService, type SearchService } from '@/Composables/useSearchService'

// Import icons using the ~icons pattern like DutyForm.vue
import IconNews from '~icons/fluent/news20-regular'
import IconPage from '~icons/fluent/document-text20-regular'
import IconDocument from '~icons/fluent/document20-regular'
import IconCalendar from '~icons/fluent/calendar20-regular'
import IconArrowRight from '~icons/fluent/arrow-right-16-filled'

interface Props {
  indexName: string
  title: string
  icon: string
  type: string
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
    case 'news': return IconNews
    case 'pages': return IconPage
    case 'documents': return IconDocument
    case 'calendar': return IconCalendar
    default: return IconPage
  }
}

const getSectionHeaderClasses = () => {
  return 'px-3 py-2 text-xs font-medium text-muted-foreground border-l-2 bg-red-50 dark:bg-red-950/30 border-vusa-red'
}

const getItemClasses = () => {
  return 'group mx-3 mb-4 p-5 rounded-lg border cursor-pointer hover:shadow-md transition-all duration-200 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-vusa-red border-zinc-200 dark:border-zinc-700 hover:border-vusa-red/50 dark:hover:border-vusa-red/50 hover:bg-red-50/30 dark:hover:bg-red-950/10'
}

const getIconClasses = () => {
  return 'w-6 h-6 rounded flex items-center justify-center bg-red-100 dark:bg-red-900/50 text-vusa-red dark:text-red-400'
}

const getLoadMoreClasses = () => {
  return 'text-vusa-red hover:text-vusa-red-secondary hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30'
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
