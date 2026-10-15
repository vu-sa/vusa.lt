<template>
  <!-- Always inline: a match fills the slot its field already has, so the caller owns the typography. -->
  <span data-slot="search-match" :data-field="match?.field">
    <template v-for="(segment, index) in match?.segments" :key="index">
      <mark v-if="segment.matched" class="bg-secondary font-[inherit] text-foreground">{{ segment.text }}</mark><template v-else>{{ segment.text }}</template>
    </template>
    <template v-if="!match">{{ title }}</template>
  </span>
</template>

<script setup lang="ts">
import type { SearchMatch } from '@/Shared/Search/matches';

defineProps<{ match?: SearchMatch; title?: string | null }>();
</script>
