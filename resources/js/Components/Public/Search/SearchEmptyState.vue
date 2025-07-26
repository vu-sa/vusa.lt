<template>
  <div class="flex-1 flex items-center justify-center p-8">
    <div class="text-center max-w-md space-y-4">
      <div class="mx-auto w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </div>
      <div>
        <h3 class="font-medium text-lg mb-2">
          {{ searchQuery ? $t('search.keep_typing_to_see_results') : $t('search.start_search') }}
        </h3>
        <p class="text-muted-foreground text-sm">
          {{ searchQuery ? $t('search.please_enter_at_least_3_characters') : $t('search.search_across_your_selected_content_types') }}
        </p>
        <!-- Show selected content types -->
        <div v-if="selectedTypes.length > 0" class="mt-3">
          <p class="text-xs text-muted-foreground mb-2">{{ $t('search.searching_in') }}:</p>
          <div class="flex flex-wrap gap-1 justify-center">
            <Badge
              v-for="type in selectedTypes"
              :key="type.id"
              variant="outline"
              class="text-xs"
            >
              {{ type.icon }} {{ $t(type.name) }}
            </Badge>
          </div>
        </div>
      </div>
      <div v-if="!searchQuery" class="space-y-2">
        <!-- Recent searches if available -->
        <div v-if="recentSearches.length > 0">
          <p class="text-xs text-muted-foreground">{{ $t('search.recent_searches') }}:</p>
          <div class="flex flex-wrap gap-2 justify-center">
            <Button 
              v-for="search in recentSearches.slice(0, 3)" 
              :key="search"
              variant="ghost" 
              size="sm" 
              class="text-xs"
              @click="$emit('selectSearch', search)"
            >
              {{ search }}
            </Button>
          </div>
        </div>
        <!-- Fallback suggestions -->
        <div v-else>
          <p class="text-xs text-muted-foreground">{{ $t('search.popular_searches') }}:</p>
          <div class="flex flex-wrap gap-2 justify-center">
            <Button 
              v-for="suggestion in popularSuggestions" 
              :key="suggestion"
              variant="ghost" 
              size="sm" 
              class="text-xs"
              @click="$emit('selectSearch', suggestion)"
            >
              {{ $t(suggestion) }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'

interface ContentType {
  id: string
  name: string
  icon: string
}

interface SearchEmptyStateProps {
  searchQuery: string
  selectedTypes: ContentType[]
  recentSearches: string[]
}

const props = defineProps<SearchEmptyStateProps>()

defineEmits<{
  (e: 'selectSearch', search: string): void
}>()

const popularSuggestions = ['Student events', 'Academic calendar', 'News updates']
</script>