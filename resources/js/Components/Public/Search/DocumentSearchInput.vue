<template>
  <BaseSearchInput
    :query="query"
    :is-searching="isSearching"
    :recent-searches="recentSearches"
    placeholder-key="search.search_documents_placeholder"
    :type-to-search="typeToSearch"
    @update:query="emit('update:query', $event)"
    @search="emit('search', $event)"
    @select-recent="emit('selectRecent', $event)"
    @clear="emit('clear')"
    @focus="emit('focus')"
    @blur="emit('blur')"
    @update:type-to-search="emit('update:typeToSearch', $event)"
    @remove-recent="emit('removeRecent', $event)"
    @clear-all-history="emit('clearAllHistory')"
  />
</template>

<script setup lang="ts">
import BaseSearchInput from './Shared/BaseSearchInput.vue'

interface Props {
  query?: string
  isSearching?: boolean
  recentSearches?: string[]
  typeToSearch?: boolean
}

interface Emits {
  (e: 'update:query', value: string): void
  (e: 'search', query: string): void
  (e: 'selectRecent', search: string): void
  (e: 'clear'): void
  (e: 'focus'): void
  (e: 'blur'): void
  (e: 'update:typeToSearch', value: boolean): void
  (e: 'removeRecent', search: string): void
  (e: 'clearAllHistory'): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()
</script>
