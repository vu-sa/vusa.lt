<template>
  <div 
    data-slot="typesense-search" 
    :class="cn('typesense-search', props.class)"
  >
    <Dialog :open="isOpen" @update:open="updateDialogState">
      <DialogContent class="sm:max-w-6xl h-[80vh] p-0 overflow-hidden grid grid-rows-[auto_1fr]">
        <DialogHeader class="px-6 pt-6 pb-0">
          <DialogTitle>{{ $t('Search') }}</DialogTitle>
        </DialogHeader>

        <AisInstantSearch 
          :search-client="searchClient"
          index-name="documents" 
          :future="{ preserveSharedStateOnUnmount: true }"
          :class-names="searchClassNames"
          class="grid grid-rows-[auto_auto_1fr] h-full overflow-hidden"
        >
          <!-- Search Input Area -->
          <div class="px-6 pb-4 border-b border-zinc-200 dark:border-zinc-700">
            <DialogDescription class="mt-0">
              <!-- Search input -->
              <div class="mt-4">
                <AisSearchBox
                >
                  <template #="{ refine, currentRefinement, isSearchStalled }">
                    <div class="relative w-full">
                      <Input 
                        type="search"
                        :model-value="currentRefinement"
                        @input="refine($event.currentTarget.value)"
                        @update:model-value="handleUpdateValue"
                        :placeholder="$t('Search...')"
                        :class="cn(
                          'w-full h-12 text-lg pl-4 pr-12 rounded-md',
                          'border border-zinc-300 dark:border-zinc-700',
                          'focus-visible:ring-2 focus-visible:ring-primary',
                          'dark:bg-zinc-900 mt-0'
                        )"
                      />
                      <div class="absolute right-3 top-1/2 -translate-y-1/2">
                        <Button type="submit" variant="ghost" size="icon" class="h-8 w-8 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                          <span class="sr-only">{{ $t('Search') }}</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </Button>
                      </div>
                    </div>
                    <span :hidden="!isSearchStalled">{{ $t('Loading...') }}</span>
                  </template>
                </AisSearchBox>
              </div>
            </DialogDescription>
          </div>

          <AisConfigure 
            :hits-per-page.camel="12" 
            :query="searchQuery" 
          />

          <!-- Tabs Area -->
          <div v-if="hasActiveResults" class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <Tabs class="w-full" v-model="activeTab">
              <TabsList class="w-full" :class="[`grid-cols-${contentTypes.length + 1}`, 'gap-1']">
                <TabsTrigger value="all">{{ $t('All') }}</TabsTrigger>
                <TabsTrigger 
                  v-for="type in contentTypes" 
                  :key="type.id" 
                  :value="type.id"
                >
                  {{ type.label() }}
                </TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
          
          <!-- Results and Preview Area -->
          <div v-if="hasActiveResults" class="grid grid-cols-[1fr_2fr] overflow-hidden">
            <!-- Results Panel - Left Side -->
            <div class="flex flex-col h-full border-r border-zinc-200 dark:border-zinc-700 overflow-hidden">
              <!-- Results List -->
              <ScrollArea class="h-full">
                <!-- Dynamic Results per Content Type -->
                <div 
                  v-for="type in contentTypes" 
                  :key="type.id"
                >
                <template v-if="activeTab === type.id || activeTab === 'all'">
                  <AisIndex :index-name="type.indexName">
                    <AisHits class="min-h-[50px]" :transform-items="transformItems">
                      <template #item="{ item, sendEvent }">
                        <div 
                          class="px-3 py-1 border-b border-zinc-100 dark:border-zinc-800 last:border-0 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                          :class="{ 'bg-zinc-100 dark:bg-zinc-800': selectedItem?.id === item.id && selectedItem?.type === type.id }"
                          @click="selectItem({...item, type: type.id}, sendEvent, `${type.badgeText()} Clicked`)"
                        >
                          <CompactListItem
                            :primary-text="item.title"
                            :secondary-text="formatDate(item[type.dateField])"
                            :category="type.category()"
                          />
                        </div>
                      </template>
                      <template #empty>
                        <EmptyState
                          v-if="activeTab === type.id"
                          :icon="type.icon"
                          :message="type.emptyMessage()"
                          @clear="clearSearch"
                        />
                      </template>
                    </AisHits>
                  </AisIndex>
                  </template>
                </div>
              </ScrollArea>
            </div>

            <!-- Preview Panel - Right Side -->
            <div class="h-full overflow-y-auto bg-white dark:bg-zinc-900">
              <div v-if="selectedItem && isSelectedItemVisible" class="p-6">
                <div class="mb-4 flex items-center justify-between">
                  <Badge>{{ getTypeBadgeText(selectedItem.type) }}</Badge>
                  <Link 
                    :href="getItemUrl(selectedItem)" 
                    @success="handleItemNavigate"
                    class="underline text-sm text-zinc-500 hover:text-primary"
                  >
                    {{ $t('View full page') }} →
                  </Link>
                </div>
                
                <div class="space-y-4">
                  <h2 class="text-2xl font-semibold">{{ selectedItem.title }}</h2>
                  
                  <div v-if="selectedItem.image_url" class="aspect-video w-full overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
                    <img 
                      :src="selectedItem.image_url" 
                      :alt="selectedItem.title"
                      class="h-full w-full object-cover"
                    >
                  </div>
                  
                  <div v-if="selectedItem.description || selectedItem.summary || selectedItem.content" class="prose dark:prose-invert max-w-none">
                    <div v-html="getItemContent(selectedItem)"></div>
                  </div>
                  
                  <div class="flex items-center justify-between pt-4 text-sm text-zinc-500 border-t border-zinc-100 dark:border-zinc-800">
                    <time>{{ formatDate(getItemDate(selectedItem)) }}</time>
                    <Button
                      variant="outline"
                      size="sm"
                      @click="navigateToItem(selectedItem)"
                    >
                      {{ $t('Open') }}
                    </Button>
                  </div>
                </div>
              </div>
              
              <div v-else class="h-full flex items-center justify-center">
                <div class="text-center p-6">
                  <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                  </div>
                  <p class="text-muted-foreground">{{ $t('Select an item to preview') }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Conditional Rendering for Search Query Length/No Query -->
          <div 
            v-else 
            class="grid place-items-center py-12 text-muted-foreground"
          >
            <p class="text-center">
              {{ searchQuery ? $t('Please enter at least 3 characters to search') : $t('Enter your search query') }}
            </p>
          </div>
        </AisInstantSearch>
      </DialogContent>
    </Dialog>

    <!-- Search Trigger Button (optional) -->
    <div v-if="showTrigger">
      <Button @click="openDialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        {{ $t('Search') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
// Base imports
import { ref, onMounted, computed, defineAsyncComponent, type HTMLAttributes, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import { cn } from '@/Utils/Shadcn/utils'

// Typesense search adapter
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter'
import {
  AisInstantSearch,
  AisSearchBox,
  AisHits,
  AisIndex,
  AisConfigure
} from 'vue-instantsearch/vue3/es'

// UI components
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'

// Local components
const CompactListItem = defineAsyncComponent(() => import('./CompactListItem.vue'))
const EmptyState = defineAsyncComponent(() => import('./EmptyState.vue'))

// Helper function for generating item URLs
const generateItemUrl = (routeName: string, params: Record<string, any>, baseParams: Record<string, any>): string => {
  try {
    return route(routeName, { ...baseParams, ...params });
  } catch (e) {
    console.error(`Error generating route "${routeName}" with params:`, params, baseParams, e);
    return '#'; // Fallback URL
  }
};

// Content type configuration for search results
const contentTypes = [
  {
    id: 'documents',
    indexName: 'documents',
    label: () => $t('Documents'),
    badgeText: () => $t('Document'),
    category: () => $t('Document'),
    icon: 'file-text',
    dateField: 'created_at',
    emptyMessage: () => $t('No documents found matching your search'),
    getUrl: (item, baseParams) => item.anonymous_url || '#'
  },
  {
    id: 'pages',
    indexName: 'pages',
    label: () => $t('Pages'),
    badgeText: () => $t('Page'),
    category: () => $t('Page'),
    icon: 'file',
    dateField: 'updated_at',
    emptyMessage: () => $t('No pages found matching your search'),
    getUrl: (item, baseParams) => generateItemUrl('page', { permalink: item?.permalink }, baseParams)
  },
  {
    id: 'news',
    indexName: 'news',
    label: () => $t('News'),
    badgeText: () => $t('News'),
    category: () => $t('News'),
    icon: 'newspaper',
    dateField: 'published_at',
    emptyMessage: () => $t('No news found matching your search'),
    getUrl: (item, baseParams) => generateItemUrl('news', { news: item?.permalink, newsString: 'naujiena' }, baseParams)
  }
]

// TypeScript interfaces
interface TypesenseConfig {
  apiKey: string
  nodes: Array<{
    host: string
    port: number
    protocol: string
    path?: string
  }>
  searchParams?: {
    query_by?: {
      documents?: string
      pages?: string
      news?: string
    }
    num_typos?: number
  }
}

interface SearchItem {
  id: string | number
  title: string
  type: 'document' | 'page' | 'news' | 'calendar'
  description?: string
  content?: string
  summary?: string
  image_url?: string
  created_at?: string
  updated_at?: string
  published_at?: string
  permalink?: string
  anonymous_url?: string
  [key: string]: any
}

interface SearchComponentProps {
  initialQuery?: string
  class?: HTMLAttributes['class']
  searchTerm?: string
  showTrigger?: boolean
  dialogOpen?: boolean
  typesenseConfig?: TypesenseConfig
}

// Component setup
const props = withDefaults(defineProps<SearchComponentProps>(), {
  initialQuery: '',
  class: '',
  searchTerm: '',
  showTrigger: false,
  dialogOpen: false,
  typesenseConfig: undefined
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'open'): void
  (e: 'update:searchTerm', value: string): void
  (e: 'update:dialogOpen', value: boolean): void
}>()

const page = usePage()
const searchQuery = ref(props.searchTerm || props.initialQuery || '')
const isOpen = ref(props.dialogOpen)
const selectedItem = ref<SearchItem | null>(null)
const activeTab = ref('all')

// Watch for changes in props
watch(() => props.searchTerm, (newVal) => {
  if (newVal !== searchQuery.value) {
    searchQuery.value = newVal
  }
}, { immediate: true })

watch(() => props.dialogOpen, (newVal) => {
  isOpen.value = newVal
}, { immediate: true })

// Watch for tab changes to check selected item visibility
watch(() => activeTab.value, (newTab) => {
  if (selectedItem.value && newTab !== 'all' && selectedItem.value.type !== newTab) {
    // Selected item isn't visible in the current tab, so clear the selection
    selectedItem.value = null
  }
})

const updateDialogState = (value: boolean) => {
  isOpen.value = value
  emit('update:dialogOpen', value)
  if (!value) {
    emit('close')
  } else {
    emit('open')
  }
}

const openDialog = () => {
  isOpen.value = true
  emit('update:dialogOpen', true)
  emit('open')
}

const handleUpdateValue = (value: string | number) => {
  searchQuery.value = value
  emit('update:searchTerm', value)
}

const selectItem = (item: SearchItem, sendEvent: Function, eventText: string) => {
  selectedItem.value = item
  sendEvent('click', item, eventText)
}

const handleItemNavigate = () => {
  updateDialogState(false)
  emit('close')
}

const navigateToItem = (item: SearchItem) => {
  router.visit(getItemUrl(item))
  updateDialogState(false)
  emit('close')
}

// Get configuration from props or use secure default fallback
const getTypesenseConfig = computed((): TypesenseConfig => {
  if (props.typesenseConfig) {
    return props.typesenseConfig
  }
  
  // Default fallback (should never be used in production)
  // This will log a warning in the console to alert developers
  console.warn('No Typesense configuration provided. Using development fallback. This should not happen in production.')
  return {
    apiKey: 'xyz',
    nodes: [
      {
        host: page.props.app.url?.replace(/^https?:\/\//, '') || 'localhost',
        port: 443,
        protocol: 'https',
        path: '/api/v1/typesense'
      }
    ],
    searchParams: {
      query_by: {
        documents: 'title,summary',
        pages: 'title,content',
        news: 'title,short'
      },
      num_typos: 1
    }
  }
})

// Create search client with secure configuration
const searchAdapter = new TypesenseInstantSearchAdapter({
  server: {
    apiKey: getTypesenseConfig.value.apiKey,
    nodes: getTypesenseConfig.value.nodes,
  },
  additionalSearchParameters: {
    query_by: getTypesenseConfig.value.searchParams?.query_by?.documents || 'title',
    num_typos: getTypesenseConfig.value.searchParams?.num_typos || 1
  }
})

const searchClient = searchAdapter.searchClient

// Styling configuration using classNames prop
const searchClassNames = {
  // Root element classes
  root: 'w-full h-full flex flex-col',
  
  // SearchBox classes
  'SearchBox': {
    'root': 'w-full',
    'form': 'w-full relative',
    'input': 'w-full h-12 text-lg pl-4 pr-12 rounded-md border border-zinc-300 dark:border-zinc-700 focus:ring-2 focus:ring-primary dark:bg-zinc-900',
    'submit': 'hidden',
    'reset': 'hidden',
    'loadingIndicator': 'hidden'
  },
  
  // Panel classes
  'Panel': {
    'root': 'rounded-lg overflow-hidden',
    'header': 'p-4',
    'body': 'p-0'
  },
  
  // Hits classes
  'Hits': {
    'root': 'w-full',
    'list': 'flex flex-col',
    'item': 'bg-transparent border-0 shadow-none p-0 m-0'
  },
  
  // Index classes
  'Index': {
    'root': 'w-full',
    'header': '',
    'body': '',
    'loadingIndicator': 'flex justify-center p-4'
  }
}

// Computed properties
const hasActiveResults = computed(() => searchQuery.value.length >= 3)

const isSelectedItemVisible = computed(() => {
  if (!selectedItem.value) return false
  return activeTab.value === 'all' || activeTab.value === selectedItem.value.type
})

// Helper methods
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return ''
  try {
    return format(new Date(dateString), 'yyyy-MM-dd')
  } catch {
    return dateString
  }
}

const getTypeBadgeText = (type: string) => {
  switch (type) {
    case 'document':
      return $t('Document')
    case 'page':
      return $t('Page')
    case 'news':
      return $t('News')
    case 'calendar':
      return $t('Event')
    default:
      return type
  }
}

const getItemContent = (item: SearchItem) => {
  if (item.type === 'news' && item.short) {
    return item.short;
  }
  return item.description || item.content || item.summary || '';
}

const getItemDate = (item: SearchItem) => {
  return item.published_at || item.updated_at || item.created_at || ''
}

const getItemUrl = (item: SearchItem): string => {
  const baseParams = {
    lang: item.lang || page.props.app.locale,
    subdomain: page.props.tenant?.subdomain ?? 'www'
  };

  // Find the content type configuration for this item
  const contentType = contentTypes.find(type => type.id === item.type);
  if (contentType?.getUrl) {
    // Use the getUrl function from the configuration
    return contentType.getUrl(item, baseParams);
  }

  // Fallback for types not explicitly in contentTypes array or without getUrl
  switch (item.type) {
    case 'calendar':
      // Assuming 'calendar.event' is the correct route name
      return generateItemUrl('calendar.event', { calendar: item?.id }, baseParams);
    default:
      console.warn(`No URL generation strategy found for item type: ${item.type}`);
      return '#'; // Default fallback
  }
}

const transformItems = (items) => {
  return items.map(item => ({
    ...item,
    title: item.title || ''
  }))
}

const clearSearch = () => {
  searchQuery.value = ''
  selectedItem.value = null
  emit('update:searchTerm', '')
}

// Initialize from URL
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  const q = urlParams.get('q')
  if (q) {
    searchQuery.value = q
    emit('update:searchTerm', q)
  }
})
</script>
