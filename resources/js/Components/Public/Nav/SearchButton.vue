<template>
  <Button
    v-bind="$attrs"
    variant="ghost"
    :title="$t('Paieška')"
    class="gap-2"
    animation="subtle"
    :aria-expanded="showSearch"
    aria-haspopup="dialog"
    aria-controls="search-modal"
    @click="openSearch"
  >
    <IFluentSearch20Filled class="h-4 w-4" aria-hidden="true" />
    <slot />
    <span v-if="!$slots.default" class="sr-only">{{ $t('Paieška') }}</span>
  </Button>

  <!-- Lazy-loaded search dialog - only loads when user clicks search -->
  <Suspense v-if="searchRequested">
    <TypesenseSearch
      v-model:search-term="searchTerm"
      v-model:dialog-open="showSearch"
      :typesense-config
    />
    <template #fallback>
      <!-- Minimal loading dialog to prevent layout shift -->
      <div v-if="showSearch" class="fixed inset-0 z-50 bg-black/20 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg">
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
            <span>{{ $t('Kraunama paieška...') }}</span>
          </div>
        </div>
      </div>
    </template>
  </Suspense>
</template>

<script setup lang="ts">
import { ref, defineAsyncComponent } from 'vue';

import { Button } from '@/Components/ui/button';

// Lazy-load the TypesenseSearch component - only loads when first requested
const TypesenseSearch = defineAsyncComponent(() =>
  import('@/Components/Public/Search/TypesenseSearch.vue'),
);

// Import the search icon
import IFluentSearch20Filled from '~icons/fluent/search-20-filled';

// State for controlling search dialog visibility
const showSearch = ref(false);
const searchTerm = ref('');
const typesenseConfig = ref(null);
const searchRequested = ref(false); // Track if search component should be loaded

// Fetch Typesense configuration from the API
const fetchTypesenseConfig = async () => {
  try {
    const response = await fetch('/api/v1/typesense/config');
    if (response.ok) {
      typesenseConfig.value = await response.json();
    }
    else {
      console.warn('Failed to fetch Typesense configuration');
    }
  }
  catch (error) {
    console.warn('Error fetching Typesense configuration:', error);
  }
};

// Function to open the search dialog
const openSearch = async () => {
  // Mark search as requested so the component starts loading
  searchRequested.value = true;

  // Fetch config if not already loaded (parallel to component loading)
  if (!typesenseConfig.value) {
    fetchTypesenseConfig();
  }

  // Show the dialog (will show loading state until component is ready)
  showSearch.value = true;
};
</script>
