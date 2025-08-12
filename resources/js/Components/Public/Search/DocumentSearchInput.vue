<template>
  <div class="flex-shrink-0 px-0 sm:px-2 lg:px-4 py-2 sm:py-3 lg:py-4">
    <div class="relative w-full max-w-3xl mx-auto">
      <!-- Search Container with Enhanced Background -->
      <div class=" relative p-3 sm:p-4
          bg-gradient-to-br from-primary/5 via-background to-secondary/5
          border border-primary/20 rounded-lg shadow-sm backdrop-blur-sm
        ">
        <div class="relative">
          <!-- Search Icon and Input -->
          <div class="relative">
            <div class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-primary/60 z-10">
              <Search class="w-4 h-4" />
            </div>

            <Input ref="inputRef" role="search" :model-value="localQuery"
              :placeholder="$t('search.search_documents_placeholder')" :class="[
                'w-full h-11 text-base pl-10 sm:pl-11 pr-20 sm:pr-36 rounded-lg', // Layout & sizing
                'border border-primary/20 bg-background/80 backdrop-blur-sm', // Border & background
                'focus-visible:ring-2 focus-visible:ring-primary', // Focus states
                'focus-visible:border-primary/40 transition-all duration-200', // Focus & transitions
                'placeholder:text-muted-foreground/60' // Placeholder styling
              ]" @input="handleInput" @keydown.enter.prevent="handleEnter" @focus="handleFocus" @blur="handleBlur" />

            <div class="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 sm:gap-2">
              <!-- Type to search toggle button -->
              <Button type="button" variant="ghost" size="icon" :class="[
                'h-8 w-8 flex-shrink-0 rounded-md', // Size & shape
                'hover:bg-primary/10 transition-all duration-200', // Hover & animation
                { 'bg-primary/15 text-primary shadow-sm': typeToSearch } // Conditional active state
              ]" :title="typeToSearch ? $t('search.disable_auto_search') : $t('search.enable_auto_search')"
                @click="toggleTypeToSearch">
                <span :class="[
                  'sr-only'
                ]">{{ typeToSearch ? $t('search.disable_auto_search') :
                  $t('search.enable_auto_search') }}</span>
                <Zap class="w-3.5 h-3.5 transition-all duration-200"
                  :class="{ 'opacity-50': !typeToSearch, 'animate-pulse-zap': typeToSearch }" />
              </Button>

              <!-- Clear button when there's text -->
              <Button v-if="localQuery && !isSearching" type="button" variant="ghost" size="icon" :class="[
                'h-8 w-8 flex-shrink-0 rounded-md', // Size & shape
                'hover:bg-destructive/10 hover:text-destructive', // Hover states
                'transition-all duration-200' // Animation
              ]" @click="handleClear">
                <span class="sr-only">{{ $t('search.clear_search_button') }}</span>
                <X class="w-3.5 h-3.5" />
              </Button>

              <!-- Search button (only shown when typeToSearch is off) -->
              <Button v-if="!isSearching && !typeToSearch" type="button" size="sm" :class="[
                'h-8 px-2 sm:px-3 flex-shrink-0 rounded-md', // Size & shape
                'bg-primary hover:bg-primary/90 text-primary-foreground', // Colors
                'shadow-sm hover:shadow-md', // Shadows
                'transition-all duration-200' // Animation
              ]" @click="handleSearch">
                <Search class="w-3.5 h-3.5 sm:mr-1.5" />
                <span class="hidden sm:inline">{{ 
                  localQuery.trim() === '' ? $t('search.show_all_button') : $t('search.search_button') 
                }}</span>
              </Button>

              <!-- Loading spinner with transition -->
              <div v-if="isSearching" class="w-8 h-8 flex items-center justify-center flex-shrink-0">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-primary border-t-transparent" />
              </div>
            </div>
          </div>

          <!-- Only show auto-search status when enabled -->
          <div v-if="typeToSearch" class="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
            <Zap class="w-3 h-3 text-primary animate-pulse-zap" />
            <span>{{ $t('search.auto_search_enabled') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Suggestions Dropdown (only shown when typeToSearch is off) -->
    <div v-if="showSuggestions && !typeToSearch && recentSearches.length > 0" :class="[
      'absolute z-50 mt-1 left-1/2 -translate-x-1/2 w-full max-w-3xl', // Position & size
      'bg-background border border-border rounded-lg', // Background & border
      'shadow-lg backdrop-blur-sm' // Effects
    ]" @mousedown.prevent>
      <!-- Recent Searches -->
      <div class="p-2">
        <div :class="[
          'flex items-center justify-between px-2 py-1 mb-2', // Layout & spacing
          'text-xs font-medium text-muted-foreground', // Typography
          'border-b border-border/50' // Border
        ]">
          <span>{{ $t('search.recent_searches') }}</span>
          <Button variant="ghost" size="sm" :class="[
            'h-5 px-2 text-xs', // Size & typography
            'hover:bg-destructive/10 hover:text-destructive' // Hover states
          ]" @click="handleClearAllHistory">
            {{ $t('search.clear_all_history') }}
          </Button>
        </div>
        <div v-for="search in recentSearches.slice(0, 5)" :key="`recent-${search}`" :class="[
          'flex items-center group rounded-md', // Layout & grouping
          'hover:bg-accent/50' // Hover state
        ]">
          <Button variant="ghost" size="sm" :class="[
            'flex-1 justify-start h-8 px-2 text-sm rounded-md' // Layout & styling
          ]" @click="handleSelectSuggestion(search)">
            <History class="w-4 h-4 mr-2 opacity-60" />
            {{ search }}
          </Button>
          <Button variant="ghost" size="sm" :class="[
            'h-8 w-8 p-0 opacity-0 group-hover:opacity-100', // Size & visibility
            'hover:bg-destructive/10 hover:text-destructive', // Hover states
            'transition-opacity' // Animation
          ]" @click.stop="handleRemoveSearch(search)">
            <X class="w-3 h-3" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watchEffect } from 'vue'
// ShadcnVue components
import {
  X,
  History,
  Search,
  Zap
} from 'lucide-vue-next'

import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'

// Icons

// Props and emits
interface Props {
  query?: string
  isSearching?: boolean
  recentSearches?: string[]
  placeholder?: string
  typeToSearch?: boolean
}

interface Emits {
  (e: 'update:query', value: string): void
  (e: 'search', query: string): void
  (e: 'selectRecent', search: string): void
  (e: 'clear'): void  // Emit when user clicks clear button
  (e: 'focus'): void
  (e: 'blur'): void
  (e: 'update:typeToSearch', value: boolean): void
  (e: 'removeRecent', search: string): void  // Emit when user removes individual search
  (e: 'clearAllHistory'): void  // Emit when user clears all history
}

const {
  query = '',
  isSearching,
  recentSearches = [],
  placeholder = '', // Will use translation fallback
  typeToSearch
} = defineProps<Props>()

const emit = defineEmits<Emits>()

// Local state
const inputRef = ref<HTMLInputElement>()
const showSuggestions = ref(false)
const localQuery = ref(query)
const isInteractingWithDropdown = ref(false)


// Event handlers
const handleInput = (event: Event) => {
  const value = (event.target as HTMLInputElement)?.value || ''

  localQuery.value = value
  emit('update:query', value)

  // Show suggestions when typing (only when typeToSearch is off)
  if (!typeToSearch && (value.length > 0 || recentSearches.length > 0)) {
    showSuggestions.value = true
  }
}

const handleSearch = () => {
  emit('search', localQuery.value)
  showSuggestions.value = false
}

const handleEnter = () => {
  handleSearch()
}

const handleClear = () => {
  localQuery.value = ''
  emit('update:query', '')
  showSuggestions.value = false
  emit('clear')  // Let the parent handle the clear logic
  focusInput()
}

const toggleTypeToSearch = () => {
  emit('update:typeToSearch', !typeToSearch)
}

const handleSelectSuggestion = (search: string) => {
  localQuery.value = search
  emit('selectRecent', search)
  showSuggestions.value = false
}

const handleRemoveSearch = (search: string) => {
  isInteractingWithDropdown.value = true
  emit('removeRecent', search)
  // Reset the flag after a short delay
  setTimeout(() => {
    isInteractingWithDropdown.value = false
  }, 100)
}

const handleClearAllHistory = () => {
  isInteractingWithDropdown.value = true
  emit('clearAllHistory')
  // Reset the flag after a short delay
  setTimeout(() => {
    isInteractingWithDropdown.value = false
  }, 100)
}

const focusInput = async () => {
  await nextTick()
  if (inputRef.value && typeof inputRef.value.focus === 'function') {
    inputRef.value.focus()
  }
}

// Handle focus and blur events
const handleFocus = () => {
  // Only show suggestions when typeToSearch is off
  if (!typeToSearch) {
    showSuggestions.value = true
  }
  emit('focus')
}

const handleBlur = (event: FocusEvent) => {
  // Don't hide suggestions if we're currently interacting with the dropdown
  if (isInteractingWithDropdown.value) {
    return
  }

  // Check if the focus is moving to an element within our dropdown
  const relatedTarget = event.relatedTarget as HTMLElement
  const currentTarget = event.currentTarget as HTMLElement

  // If the focus is moving to a descendant of our component, don't hide suggestions
  if (relatedTarget && currentTarget.contains(relatedTarget)) {
    return
  }

  // Delay hiding suggestions to allow clicking on them
  setTimeout(() => {
    // Double-check that we're still not interacting with dropdown
    if (!isInteractingWithDropdown.value) {
      showSuggestions.value = false
      emit('blur')
    }
  }, 150)
}

// Watch for prop changes
const syncQuery = computed(() => query ?? '')
watchEffect(() => {
  if (syncQuery.value !== localQuery.value) {
    localQuery.value = syncQuery.value
  }
})

// Expose focus method
defineExpose({
  focusInput
})
</script>

<style scoped>
/* Animation for loading state */
.animate-pulse-subtle {
  animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.8;
  }
}

/* Subtle zap animation */
.animate-pulse-zap {
  animation: pulse-zap 2s ease-in-out infinite;
}

@keyframes pulse-zap {

  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}
</style>
