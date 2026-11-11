<script setup lang="ts">
import { Search, X } from '@lucide/vue';

import { searchFieldClass } from '@/Components/ui/control';

defineProps<{ label: string; placeholder: string }>();
const query = defineModel<string>({ required: true });
</script>

<template>
  <div class="relative" data-slot="wiki-search">
    <Search class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
    <input
      v-model="query"
      type="search"
      data-admin-collection-search
      autocomplete="off"
      :aria-label="label"
      :placeholder
      :class="[searchFieldClass, 'h-14 pl-12 text-base [&::-webkit-search-cancel-button]:hidden']"
    >
    <button
      v-if="query"
      type="button"
      class="absolute top-1/2 right-1.5 flex size-11 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground"
      :aria-label="$t('Išvalyti paiešką')"
      @click="query = ''"
    >
      <X class="size-4" aria-hidden="true" />
    </button>
  </div>
</template>
