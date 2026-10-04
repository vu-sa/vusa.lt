<template>
  <span v-if="inline || (match && text !== title)" :class="inline ? '' : ['block font-normal text-muted-foreground', compact ? 'line-clamp-1 text-xs leading-4' : 'line-clamp-2 text-sm']" :data-slot="inline ? 'search-title-match' : 'search-match'">
    <template v-for="(segment, index) in match?.segments" :key="index">
      <mark v-if="segment.matched" :class="['bg-secondary text-foreground', inline ? 'font-[inherit]' : 'font-medium']">{{ segment.text }}</mark><template v-else>{{ segment.text }}</template>
    </template>
    <template v-if="!match">{{ title }}</template>
  </span>
</template>

<script setup lang="ts">
import type { SearchMatch } from '@/Shared/Search/matches';
import { computed } from 'vue';

const props = defineProps<{ match?: SearchMatch; compact?: boolean; inline?: boolean; title?: string }>();
const text = computed(() => props.match?.segments.map(segment => segment.text).join(''));
</script>
