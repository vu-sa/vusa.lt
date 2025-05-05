<template>
  <NButton v-bind="$attrs" :title="$t('Paieška')" text @click="openSearchDialog">
    <template #icon>
      <IFluentSearch20Filled />
    </template>
    <slot />
  </NButton>
  <TypesenseSearch 
    v-model:searchTerm="searchTerm" 
    v-model:dialogOpen="showSearch"
    :config="typesenseConfig"
  />
</template>

<script setup lang="ts">
import { ref } from "vue";
import TypesenseSearch from "@/Components/Public/Search/TypesenseSearch.vue";

// State for controlling search dialog visibility
const showSearch = ref(false);
const searchTerm = ref('');

const typesenseConfig = {
  apiKey: import.meta.env.VITE_TYPESENSE_API_KEY,
  host: import.meta.env.VITE_TYPESENSE_HOST,
  port: Number(import.meta.env.VITE_TYPESENSE_PORT),
  protocol: import.meta.env.VITE_TYPESENSE_PROTOCOL
};

// Open search dialog
const openSearchDialog = () => {
  showSearch.value = true;
};
</script>
