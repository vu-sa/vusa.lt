<template>
  <div class="flex flex-col gap-3" data-slot="representative-activity-skeleton" aria-hidden="true">
    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
      <div v-for="row in 4" :key="row" class="flex flex-col gap-1">
        <Skeleton class="h-8 w-20" />
        <Skeleton class="h-4 w-32 max-w-full" />
      </div>
    </div>
    <div class="divide-y divide-border/60">
      <div v-for="row in 3" :key="row" class="flex items-center gap-3 py-2">
        <Skeleton class="size-7 shrink-0 rounded-full" />
        <div class="flex flex-1 flex-col gap-1.5">
          <Skeleton class="h-4 w-32 max-w-full" />
          <Skeleton class="h-3 w-24 max-w-full" />
        </div>
        <Skeleton class="h-3 w-14" />
      </div>
    </div>
    <Skeleton class="h-9 w-32" />
  </div>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';
</script>
