<template>
  <ul class="grid grid-cols-2 gap-x-6 gap-y-4 border-t border-border pt-4 lg:grid-cols-4" data-slot="overview-numbers-skeleton" aria-hidden="true">
    <li v-for="number in count" :key="number" class="flex min-h-11 flex-col gap-1">
      <Skeleton class="h-8 w-12" />
      <Skeleton class="h-4 w-28 max-w-full" />
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';

defineProps<{ count: number }>();
</script>
