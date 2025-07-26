<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Results List -->
    <div class="flex flex-col h-full overflow-hidden">
      <!-- Stats Header -->
      <div class="px-6 py-2 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
        <div class="text-xs text-muted-foreground">
          <!-- Show total stats from search service for both grouped and unified views -->
          {{ totalResultCount.toLocaleString() }} {{ $t('search.results') }}
        </div>
      </div>

      <!-- Grouped Results (default) -->
      <ScrollArea v-if="groupResults" class="h-full">
        <div class="space-y-6 p-6" role="listbox" aria-label="Search results">
          <!-- Render sections for enabled content types in configured order -->
          <SearchResultSection 
            v-for="contentType in orderedTypes"
            :key="contentType.id"
            :index-name="contentType.indexName"
            :title="$t(contentType.name)"
            :icon="contentType.icon"
            :type="contentType.id"
            :color="contentType.color"
            :result-order="resultOrder"
            @navigate-to-item="$emit('navigateToItem', $event)"
            @update-result-count="(count) => $emit('updateResultCount', contentType.id, count)"
            @update-total-hits="(totalHits) => $emit('updateTotalHits', contentType.id, totalHits)"
          />
        </div>
      </ScrollArea>
      
      <!-- Unified Results (alternative view) -->
      <ScrollArea v-else class="h-full">
        <UnifiedResults
          :enabled-content-types="selectedTypes"
          :result-order="resultOrder"
          @navigate-to-item="$emit('navigateToItem', $event)"
          @update-result-count="(count) => $emit('updateResultCount', 'unified', count)"
          @update-total-hits="(contentTypeId, totalHits) => $emit('updateTotalHits', contentTypeId, totalHits)"
          @toggle-view="$emit('toggleView')"
        />
      </ScrollArea>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n'
import { ScrollArea } from '@/Components/ui/scroll-area'
import SearchResultSection from './SearchResultSection.vue'
import UnifiedResults from './UnifiedResults.vue'
import type { SearchItem } from '@/Composables/useSearchUtils'

interface ContentType {
  id: string
  name: string
  icon: string
  color: string
  indexName: string
}

interface SearchResultsAreaProps {
  groupResults: boolean
  orderedTypes: ContentType[]
  selectedTypes: ContentType[]
  resultOrder: string
  totalResultCount: number
}

defineProps<SearchResultsAreaProps>()

defineEmits<{
  (e: 'navigateToItem', item: SearchItem): void
  (e: 'updateResultCount', typeId: string, count: number): void
  (e: 'updateTotalHits', typeId: string, totalHits: number): void
  (e: 'toggleView'): void
}>()
</script>