<template>
  <DetailSection :title>
    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in skeletonCount" :key="i" class="h-9 animate-pulse rounded-md bg-muted/50" />
    </div>

    <template v-else-if="hasContent">
      <slot />
    </template>

    <p v-else class="text-sm text-muted-foreground">
      {{ emptyMessage }}
    </p>
  </DetailSection>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import DetailSection from './DetailSection.vue';

const props = withDefaults(defineProps<{
  title: string;
  isFetching: boolean;
  hasContent: boolean;
  emptyMessage: string;
  skeletonCount?: number;
}>(), {
  skeletonCount: 3,
});

const isLoading = computed(() => props.isFetching && !props.hasContent);
</script>
