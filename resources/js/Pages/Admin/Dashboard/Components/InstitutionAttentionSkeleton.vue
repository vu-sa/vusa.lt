<template>
  <section class="flex flex-col gap-3" data-slot="institution-attention-skeleton" aria-hidden="true">
    <div class="flex items-center gap-2 border-b border-border pb-3">
      <Skeleton class="size-4" />
      <Skeleton class="h-4 w-44 max-w-full" />
    </div>
    <ul class="divide-y divide-border/60">
      <li v-for="row in 3" :key="row" class="flex flex-wrap items-center gap-x-4 gap-y-2 py-4">
        <div class="flex min-w-0 flex-1 basis-48 flex-col gap-2">
          <Skeleton class="h-5 w-44 max-w-full" />
          <Skeleton class="h-3 w-32 max-w-full" />
        </div>
        <div class="flex items-center gap-3">
          <Skeleton class="h-6 w-20" />
          <Skeleton class="h-9 w-32" />
        </div>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';
</script>
