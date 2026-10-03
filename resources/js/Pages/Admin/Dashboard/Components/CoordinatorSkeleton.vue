<template>
  <section class="flex flex-col gap-3" data-slot="coordinator-skeleton" aria-hidden="true">
    <div class="flex items-center gap-2 border-b border-border pb-3">
      <Skeleton class="size-4" />
      <Skeleton class="h-4 w-36" />
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
      <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
        <Skeleton class="size-8 shrink-0 rounded-full" />
        <div class="flex min-w-0 flex-col gap-2">
          <Skeleton class="h-4 w-36 max-w-full" />
          <Skeleton class="h-3 w-24 max-w-full" />
        </div>
      </div>
      <Skeleton class="h-9 w-full sm:w-24" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';
</script>
