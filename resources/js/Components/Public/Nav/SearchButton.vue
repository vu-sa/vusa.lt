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
  <TypesenseSearch 
    v-model:search-term="searchTerm" 
    v-model:dialog-open="showSearch"
    :typesense-config
  />
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";

import { Button } from "@/Components/ui/button";
import TypesenseSearch from "@/Components/Public/Search/TypesenseSearch.vue";

// Import the search icon
import IFluentSearch20Filled from "~icons/fluent/search-20-filled";

// State for controlling search dialog visibility
const showSearch = ref(false);
const searchTerm = ref('');
const typesenseConfig = ref(null);

// Fetch Typesense configuration from the API
const fetchTypesenseConfig = async () => {
  try {
    const response = await fetch('/api/v1/typesense/config');
    if (response.ok) {
      typesenseConfig.value = await response.json();
    } else {
      console.warn('Failed to fetch Typesense configuration');
    }
  } catch (error) {
    console.warn('Error fetching Typesense configuration:', error);
  }
};

// Load configuration on component mount
onMounted(() => {
  fetchTypesenseConfig();
});

// Function to open the search dialog
const openSearch = () => {
  showSearch.value = true;
};
</script>
