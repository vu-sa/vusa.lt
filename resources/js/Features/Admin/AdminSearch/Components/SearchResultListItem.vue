<template>
  <button
    type="button"
    :class="[
      'group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors',
      selected ? 'bg-accent ring-1 ring-primary/20' : 'hover:bg-accent/60',
    ]"
    @click="$emit('select')"
  >
    <SearchHitRow :hit="hit" :selected="selected" />
  </button>
</template>

<script setup lang="ts">
import SearchHitRow from './SearchHitRow.vue';
import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

defineProps<{
  hit: NormalizedSearchHit;
  selected?: boolean;
}>();

defineEmits<{
  select: [];
}>();
</script>
