<template>
  <!-- Shaped like the chart it stands in for: toolbar, label column, axis, rows. -->
  <div class="flex flex-col gap-2" data-slot="timeline-gantt-skeleton" aria-hidden="true">
    <div class="flex items-center justify-between gap-3">
      <Skeleton class="h-5 w-28" />
      <Skeleton class="h-6 w-40" />
    </div>

    <div class="flex h-96 w-full overflow-hidden border border-border">
      <div class="w-56 shrink-0 border-r border-border bg-card">
        <div class="h-[22px] border-b border-border bg-muted/40" />
        <div v-for="row in ROWS" :key="row" class="flex items-center justify-between gap-2 border-b border-border/60 px-3 py-2.5">
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-3 w-10" />
        </div>
      </div>

      <div class="flex-1 bg-background">
        <div class="flex h-[22px] items-center gap-8 border-b border-border px-4">
          <Skeleton v-for="tick in 6" :key="tick" class="h-2.5 w-8" />
        </div>
        <div v-for="row in ROWS" :key="row" class="flex h-[41px] items-center border-b border-border/60 px-4">
          <Skeleton class="h-1.5" :style="{ width: `${20 + ((row * 37) % 45)}%`, marginLeft: `${(row * 23) % 40}%` }" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';

const ROWS = 8;
</script>
