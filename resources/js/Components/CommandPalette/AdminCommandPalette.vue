<template>
  <CommandDialog v-model:open="isOpen" :title="$t('Komandų paletė')"
    :description="$t('Ieškokite veiksmų, posėdžių ir darbotvarkės punktų')">
    <!-- Custom search input (not CommandInput to avoid internal filtering) -->
    <div class="flex h-12 items-center gap-2 border-b px-3">
      <div class="relative flex items-center justify-center size-4">
        <Search v-if="!isSearching" class="size-4 shrink-0 text-muted-foreground/50" />
        <div v-else class="size-4 rounded-full border-2 border-primary/30 border-t-primary animate-spin" />
      </div>
      <input ref="searchInputRef" v-model="query" type="text" :placeholder="$t('Ieškoti veiksmų, posėdžių...')"
        class="flex-1 h-12 bg-transparent text-sm outline-none placeholder:text-muted-foreground/50"
        @keydown.escape="close">
    </div>

    <CommandList class="max-h-[60vh] scroll-py-2">
      <!-- Loading skeleton -->
      <div v-if="isSearching && query" class="p-2 space-y-1">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 px-3 py-3 rounded-lg">
          <div class="size-9 rounded-lg bg-muted/50 animate-pulse" />
          <div class="flex-1 space-y-2">
            <div class="h-4 w-3/4 rounded bg-muted/50 animate-pulse" />
            <div class="h-3 w-1/2 rounded bg-muted/30 animate-pulse" />
          </div>
        </div>
      </div>

      <template v-else>
        <!-- Recent items (when query is empty) -->
        <CommandGroup v-if="!query && recentItems.length > 0" :heading="$t('Neseniai')" class="px-2">
          <CommandItem v-for="item in recentItems" :key="`recent-${item.type}-${item.id}`"
            :value="`recent-${item.type}-${item.id}`"
            class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent"
            @select="handleRecentSelect(item)">
            <div class="flex items-center gap-3 w-full">
              <div
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted/50 text-muted-foreground group-hover:bg-background group-hover:shadow-sm transition-all">
                <Clock class="size-4" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="block font-medium truncate text-sm">{{ item.title }}</span>
                <span class="text-xs text-muted-foreground">
                  {{ getRecentTypeBadge(item.type) }}
                </span>
              </div>
              <ChevronRight
                class="size-4 text-muted-foreground/50 opacity-0 group-hover:opacity-100 transition-opacity" />
            </div>
          </CommandItem>
        </CommandGroup>

        <!-- Quick Actions -->
        <CommandGroup v-if="filteredActions.length > 0" :heading="$t('Veiksmai')" class="px-2">
          <ActionResult v-for="action in filteredActions" :key="action.id" :action />
        </CommandGroup>

        <!-- News (Typesense search) -->
        <CommandGroup v-if="searchResults.news.length > 0" :heading="$t('Naujienos')" class="px-2">
          <NewsResult v-for="newsItem in searchResults.news" :key="`news-${newsItem.id}`" :news="newsItem" />
        </CommandGroup>

        <!-- Pages (Typesense search) -->
        <CommandGroup v-if="searchResults.pages.length > 0" :heading="$t('Puslapiai')" class="px-2">
          <PageResult v-for="pageItem in searchResults.pages" :key="`page-${pageItem.id}`" :page="pageItem" />
        </CommandGroup>

        <!-- Calendar (Typesense search) -->
        <CommandGroup v-if="searchResults.calendar.length > 0" :heading="$t('Kalendorius')" class="px-2">
          <CalendarResult v-for="event in searchResults.calendar" :key="`calendar-${event.id}`" :event />
        </CommandGroup>

        <!-- Institutions (Typesense search) -->
        <CommandGroup v-if="searchResults.institutions.length > 0" :heading="$t('Institucijos')" class="px-2">
          <InstitutionResult v-for="institution in searchResults.institutions" :key="`institution-${institution.id}`" :institution />
        </CommandGroup>

        <!-- Meetings (Typesense search) -->
        <CommandGroup v-if="searchResults.meetings.length > 0" :heading="$t('Posėdžiai')" class="px-2">
          <MeetingResult 
            v-for="meeting in searchResults.meetings" 
            :key="`meeting-${meeting.id}`" 
            :meeting 
            :is-related="isFromRelatedInstitution('meetings', meeting.institution_ids)"
          />
        </CommandGroup>

        <!-- Agenda Items (Typesense search) -->
        <CommandGroup v-if="searchResults.agendaItems.length > 0" :heading="$t('Darbotvarkės punktai')" class="px-2">
          <AgendaItemResult 
            v-for="item in searchResults.agendaItems" 
            :key="`agenda-${item.id}`" 
            :item 
            :is-related="isFromRelatedInstitution('agenda_items', item.institution_ids)"
          />
        </CommandGroup>

        <!-- Documents (Typesense search) -->
        <CommandGroup v-if="searchResults.documents.length > 0" :heading="$t('Dokumentai')" class="px-2">
          <DocumentResult v-for="doc in searchResults.documents" :key="`document-${doc.id}`" :document="doc" />
        </CommandGroup>

        <!-- Rate limit warning -->
        <div v-if="searchError && searchError.includes('užklausų')" class="mx-4 my-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
          <p class="text-sm text-amber-700 dark:text-amber-400 flex items-center gap-2">
            <Clock class="size-4" />
            {{ searchError }}
          </p>
        </div>

        <!-- Empty state -->
        <div v-if="query && !hasResults && !isSearching && !searchError" class="py-14 text-center">
          <div class="mx-auto w-12 h-12 rounded-full bg-muted/50 flex items-center justify-center mb-4">
            <SearchX class="size-6 text-muted-foreground/50" />
          </div>
          <p class="text-sm font-medium text-foreground">
            {{ $t('Rezultatų nerasta') }}
          </p>
          <p class="text-xs text-muted-foreground mt-1">
            {{ $t('Pabandykite kitą paieškos frazę') }}
          </p>
        </div>

        <!-- Initial state hint -->
        <div v-if="!query && recentItems.length === 0" class="py-10 text-center">
          <div class="mx-auto w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-4">
            <Sparkles class="size-5 text-primary" />
          </div>
          <p class="text-sm font-medium text-foreground">
            {{ $t('Pradėkite rašyti') }}
          </p>
          <p class="text-xs text-muted-foreground mt-1">
            {{ $t('arba naršykite veiksmus žemiau') }}
          </p>
        </div>
      </template>
    </CommandList>

    <!-- Footer with keyboard hints -->
    <div class="flex items-center justify-between border-t bg-muted/30 px-4 py-2.5">
      <div class="flex items-center gap-5 text-xs text-muted-foreground">
        <span class="flex items-center gap-1.5">
          <span class="flex gap-0.5">
            <kbd
              class="inline-flex size-5 items-center justify-center rounded border bg-background font-mono text-[10px] shadow-sm">
              <ArrowUp class="size-3" />
            </kbd>
            <kbd
              class="inline-flex size-5 items-center justify-center rounded border bg-background font-mono text-[10px] shadow-sm">
              <ArrowDown class="size-3" />
            </kbd>
          </span>
          <span class="text-muted-foreground/70">{{ $t('naršyti') }}</span>
        </span>
        <span class="flex items-center gap-1.5">
          <kbd
            class="inline-flex h-5 items-center justify-center rounded border bg-background px-1.5 font-mono text-[10px] shadow-sm">
            ↵
          </kbd>
          <span class="text-muted-foreground/70">{{ $t('pasirinkti') }}</span>
        </span>
        <span class="flex items-center gap-1.5">
          <kbd
            class="inline-flex h-5 items-center justify-center rounded border bg-background px-1.5 font-mono text-[10px] shadow-sm">
            esc
          </kbd>
          <span class="text-muted-foreground/70">{{ $t('uždaryti') }}</span>
        </span>
      </div>
      <div class="text-[10px] text-muted-foreground/50 font-medium tracking-wide uppercase">
        VU SA
      </div>
    </div>
  </CommandDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { useDebounceFn } from '@vueuse/core'
import {
  Search,
  Clock,
  ArrowUp,
  ArrowDown,
  ChevronRight,
  SearchX,
  Sparkles
} from 'lucide-vue-next'


import { useCommandActions } from './useCommandActions'
import MeetingResult from './results/MeetingResult.vue'
import AgendaItemResult from './results/AgendaItemResult.vue'
import ActionResult from './results/ActionResult.vue'
import NewsResult from './results/NewsResult.vue'
import PageResult from './results/PageResult.vue'
import CalendarResult from './results/CalendarResult.vue'
import InstitutionResult from './results/InstitutionResult.vue'
import DocumentResult from './results/DocumentResult.vue'

import { useAdminSearch, type MultiSearchResults } from '@/Composables/useAdminSearch'
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette'
import {
  CommandDialog,
  CommandList,
  CommandGroup,
  CommandItem
} from '@/Components/ui/command'

// Command palette state
const { isOpen, query, recentItems, close } = useCommandPalette()

// Admin search
const { multiSearch, initialize: initializeSearch, isRateLimited, isFromRelatedInstitution } = useAdminSearch()

// Command actions
const { filterActions } = useCommandActions()

// Local state
const isSearching = ref(false)
const searchError = ref<string | null>(null)
const searchResults = ref<MultiSearchResults>({
  meetings: [],
  agendaItems: [],
  news: [],
  pages: [],
  calendar: [],
  institutions: [],
  documents: []
})
const searchInputRef = ref<HTMLInputElement | null>(null)

// Filtered actions based on query
const filteredActions = computed(() => {
  return filterActions(query.value).slice(0, 6) // Limit to 6 actions
})

// Check if we have any results
const hasResults = computed(() => {
  return (
    filteredActions.value.length > 0 ||
    searchResults.value.meetings.length > 0 ||
    searchResults.value.agendaItems.length > 0 ||
    searchResults.value.news.length > 0 ||
    searchResults.value.pages.length > 0 ||
    searchResults.value.calendar.length > 0 ||
    searchResults.value.institutions.length > 0 ||
    searchResults.value.documents.length > 0
  )
})

// Debounced search function - 300ms debounce to reduce request frequency
const performSearch = useDebounceFn(async (searchQuery: string) => {
  if (!searchQuery.trim()) {
    searchResults.value = { meetings: [], agendaItems: [], news: [], pages: [], calendar: [], institutions: [], documents: [] }
    isSearching.value = false
    return
  }

  // Skip if rate limited
  if (isRateLimited.value) {
    searchError.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.')
    isSearching.value = false
    return
  }

  isSearching.value = true
  searchError.value = null

  try {
    searchResults.value = await multiSearch(searchQuery, {
      meetingsLimit: 3,
      agendaItemsLimit: 3,
      newsLimit: 3,
      pagesLimit: 3,
      calendarLimit: 3,
      institutionsLimit: 3,
      documentsLimit: 3
    })
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Search failed'
    // Show user-friendly message for rate limiting
    if (message.includes('Too many requests')) {
      searchError.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.')
    } else {
      searchError.value = message
    }
    // Don't clear results on error - keep previous results visible
  } finally {
    isSearching.value = false
  }
}, 300)

// Watch query changes
watch(query, (newQuery) => {
  if (newQuery.trim()) {
    performSearch(newQuery)
  } else {
    searchResults.value = { meetings: [], agendaItems: [], news: [], pages: [], calendar: [], institutions: [], documents: [] }
  }
})

// Track if search has been initialized to prevent duplicate calls
let searchInitialized = false

// Initialize search and focus input when opened
watch(isOpen, (opened) => {
  if (opened) {
    // Only initialize once per session
    if (!searchInitialized) {
      initializeSearch()
      searchInitialized = true
    }
    // Focus the search input after dialog opens
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  }
})

// Handle recent item selection
const handleRecentSelect = (item: RecentItem) => {
  close()

  if (item.href) {
    router.visit(item.href)
  } else if (item.type === 'action') {
    // For actions, find the action and execute it
    const actions = filterActions('')
    const action = actions.find((a) => a.id === item.id)
    if (action) {
      action.action()
    }
  }
}

// Get badge text for recent item type
const getRecentTypeBadge = (type: RecentItem['type']): string => {
  switch (type) {
    case 'meeting':
      return $t('Posėdis')
    case 'agenda_item':
      return $t('Darbotvarkės punktas')
    case 'action':
      return $t('Veiksmas')
    case 'news':
      return $t('Naujiena')
    case 'page':
      return $t('Puslapis')
    case 'calendar':
      return $t('Įvykis')
    case 'institution':
      return $t('Institucija')
    case 'document':
      return $t('Dokumentas')
    default:
      return ''
  }
}
</script>
