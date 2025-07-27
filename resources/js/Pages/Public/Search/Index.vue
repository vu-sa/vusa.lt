<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-center">
      {{ $t('Paieška') }}
    </h1>

    <div class="max-w-3xl mx-auto">
      <div class="search-container mb-8">
        <div class="relative">
          <Input 
            type="search"
            v-model="searchTerm"
            :placeholder="$t('Search...')"
            @keydown.enter="openSearchDialog"
            class="search-input w-full h-12 text-lg pl-4 pr-12 rounded-md"
          />
          <div class="absolute right-3 top-1/2 -translate-y-1/2">
            <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="openSearchDialog">
              <span class="sr-only">{{ $t('Search') }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </Button>
          </div>
        </div>
      </div>

      <div class="text-center mt-12">
        <p class="text-zinc-500 dark:text-zinc-400">
          {{ $t('Use the search box above to search through documents, pages, and news.') }}
        </p>
      </div>
      
      <!-- Search dialog -->
      <TypesenseSearch 
        v-model:searchTerm="searchTerm" 
        v-model:dialogOpen="showSearch"
        :initial-query="initialQuery" 
        :config="typesenseConfig" 
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import TypesenseSearch from '@/Components/Public/Search/TypesenseSearch.vue'

// Get Typesense configuration from environment variables
const typesenseConfig = {
  apiKey: import.meta.env.VITE_TYPESENSE_API_KEY,
  host: import.meta.env.VITE_TYPESENSE_HOST,
  port: Number(import.meta.env.VITE_TYPESENSE_PORT),
  protocol: import.meta.env.VITE_TYPESENSE_PROTOCOL
};

// Create local state for search term and dialog visibility
const searchTerm = ref('');
const showSearch = ref(false);

// Props
const props = defineProps<{
  initialQuery?: string
}>()

// Open search dialog
const openSearchDialog = () => {
  if (searchTerm.value.length > 0) {
    showSearch.value = true;
  }
};

// Initialize from props or URL
onMounted(() => {
  if (props.initialQuery) {
    searchTerm.value = props.initialQuery;
    // Open search dialog automatically if there's an initial query
    if (searchTerm.value.length >= 3) {
      showSearch.value = true;
    }
  }
})
</script>

<style>
.search-input {
  border: 1px solid var(--border, #e2e8f0);
}

.search-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px var(--ring, rgba(66, 153, 225, 0.5));
  border-color: var(--ring, rgba(66, 153, 225, 0.5));
}

/* Dark mode adjustments */
.dark .search-input {
  border-color: var(--border-dark, #2d3748);
  background-color: rgba(17, 24, 39, 0.8);
  color: #fff;
}
</style>
