<template>
  <AisIndex :index-name="indexName">
    <!-- Track total hits for accurate counts -->
    <AisStats>
      <template #default="{ nbHits }">
        <span style="display: none">{{ updateTotalHits(nbHits) }}</span>
      </template>
    </AisStats>
    
    <AisInfiniteHits 
      :transform-items="transformItems"
      :show-previous="false"
      class="hide-default-buttons"
    >
      <template #default="{ items, isLastPage, refineNext }">
        <span style="display: none">{{ updateResultsCount(items.length) }}</span>
        
        <!-- Only render anything if there are items -->
        <div v-if="items.length > 0">
          <div class="space-y-2">
            <!-- Section Header with total hits count -->
            <div :class="getSectionHeaderClasses(color)">
              {{ icon }} {{ title }} ({{ currentHits }})
            </div>

            <!-- Results -->
            <div class="space-y-2">
              <div v-for="item in items" :key="`${item.id}-${type}`" :class="getItemClasses(color, isSelected(item))"
                @click="() => $emit('selectItem', { ...item, type })">
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0">
                    <div :class="getIconClasses(color)">
                      <component :is="getIconComponent(type)" class="w-3 h-3" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <time class="text-xs text-muted-foreground">{{ formatDate(getItemDate(item)) }}</time>
                    </div>
                    <h3 class="font-medium text-sm leading-tight mb-1">
                      <AisHighlight attribute="title" :hit="item" />
                      <AisHighlight v-if="!item.title && item.name" attribute="name" :hit="item" />
                      <span v-if="!item.title && !item.name">{{ item.name || item.title || $t('Untitled') }}</span>
                    </h3>
                    <div v-if="getItemContent(item)" class="text-xs text-muted-foreground leading-relaxed line-clamp-2">
                      {{ stripHtml(getItemContent(item)) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load More Button - only show if there are items and more pages available -->
              <div v-if="!isLastPage && items.length > 0" class="px-3 py-2">
                <Button variant="ghost" size="sm" :class="getLoadMoreClasses(color)"
                  class="w-full justify-center text-xs" @click="refineNext">
                  <ArrowRightIcon class="mr-1 w-3.5 h-3.5" />
                  {{ $t('Show more :type', { type: title.toLowerCase() }) }}
                </Button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </AisInfiniteHits>
  </AisIndex>
</template>

<script setup lang="ts">
import { computed, h, ref, watch } from 'vue'
import { AisIndex, AisInfiniteHits, AisHighlight, AisStats } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'

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
  selectedItem: any
  resultOrder?: 'relevance' | 'date' | 'type'
}

const props = withDefaults(defineProps<Props>(), {
  resultOrder: 'relevance'
})

const emit = defineEmits<{
  selectItem: [item: any]
  updateResultCount: [count: number]
  updateTotalHits: [totalHits: number]
}>()

// Store current stats and results for tracking
const currentHits = ref(0)
const currentResults = ref(0)

// Update functions that can be safely called in templates
const updateTotalHits = (hits: number) => {
  if (currentHits.value !== hits) {
    currentHits.value = hits
    emit('updateTotalHits', hits)
  }
  return ''
}

const updateResultsCount = (count: number) => {
  if (currentResults.value !== count) {
    currentResults.value = count
    emit('updateResultCount', count)
  }
  return ''
}

// Remove the problematic queryKey that causes constant re-rendering
const transformItems = (items: any[]) => {
  const transformedItems = items.map(item => ({
    ...item,
    title: item.title || item.name || '',
    type: props.type
  }))

  // Update results count when items transform
  currentResults.value = transformedItems.length

  // Apply additional sorting based on result order preference
  if (props.resultOrder === 'date') {
    return transformedItems.sort((a, b) => {
      const aDate = getItemDate(a)
      const bDate = getItemDate(b)
      if (!aDate && !bDate) return 0
      if (!aDate) return 1
      if (!bDate) return -1
      
      // Convert to comparable timestamps
      const aTime = typeof aDate === 'number' ? aDate : new Date(aDate).getTime()
      const bTime = typeof bDate === 'number' ? bDate : new Date(bDate).getTime()
      
      return bTime - aTime // Most recent first
    })
  }
  
  // For relevance and type, use Typesense's default ranking
  return transformedItems
}

const isSelected = (item: any) => {
  return props.selectedItem?.id === item.id && props.selectedItem?.type === props.type
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

const getItemClasses = (color: string, isSelected: boolean) => {
  const baseClasses = 'mx-2 mb-2 p-3 rounded-lg border cursor-pointer hover:shadow-sm transition-all duration-200 bg-white dark:bg-zinc-900'

  let colorClasses = ''
  if (isSelected) {
    switch (color) {
      case 'blue':
        colorClasses = 'border-blue-500 dark:border-blue-400 shadow-md bg-blue-50/50 dark:bg-blue-950/20'
        break
      case 'green':
        colorClasses = 'border-green-500 dark:border-green-400 shadow-md bg-green-50/50 dark:bg-green-950/20'
        break
      case 'purple':
        colorClasses = 'border-purple-500 dark:border-purple-400 shadow-md bg-purple-50/50 dark:bg-purple-950/20'
        break
      case 'amber':
        colorClasses = 'border-amber-500 dark:border-amber-400 shadow-md bg-amber-50/50 dark:bg-amber-950/20'
        break
      default:
        colorClasses = 'border-zinc-500 dark:border-zinc-400 shadow-md bg-zinc-50/50 dark:bg-zinc-950/20'
    }
  } else {
    switch (color) {
      case 'blue':
        colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600'
        break
      case 'green':
        colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-green-300 dark:hover:border-green-600'
        break
      case 'purple':
        colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-purple-300 dark:hover:border-purple-600'
        break
      case 'amber':
        colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-amber-300 dark:hover:border-amber-600'
        break
      default:
        colorClasses = 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
    }
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
  switch (props.type) {
    case 'news': return item.short
    case 'documents': return item.summary
    case 'pages': return item.content?.slice(0, 120) + '...'
    case 'calendar': return item.description
    default: return item.summary || item.content || item.description
  }
}
</script>

<style scoped>
/* Hide the default InstantSearch load more button that appears even when slots are overridden */
:deep(.ais-InfiniteHits-loadMore) {
  display: none !important;
}

/* Also hide any disabled load more buttons */
:deep(.ais-InfiniteHits-loadMore--disabled) {
  display: none !important;
}

/* Hide any other default InstantSearch buttons that might appear */
:deep(.hide-default-buttons .ais-InfiniteHits-loadMore) {
  display: none !important;
}
</style>
