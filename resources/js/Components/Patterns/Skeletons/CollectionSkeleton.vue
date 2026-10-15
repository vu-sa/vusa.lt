<template>
  <div
    data-slot="collection-skeleton"
    :class="cn('animate-pulse space-y-4', props.class)"
  >
    <!-- Rows View Skeleton -->
    <div
      v-if="viewMode === 'rows'"
      class="divide-y divide-border border-y border-border"
    >
      <div
        v-for="n in rows"
        :key="n"
        class="flex items-center gap-4 py-3.5 sm:py-4"
      >
        <!-- Icon / Date plate skeleton -->
        <div class="size-10 shrink-0 border border-border bg-secondary/80" />

        <!-- Title & Subtitle lines -->
        <div class="min-w-0 flex-1 space-y-2">
          <div
            class="h-4 bg-secondary"
            :style="{ width: `${45 + ((n * 13) % 35)}%` }"
          />
          <div
            class="h-3 bg-secondary/70"
            :style="{ width: `${25 + ((n * 17) % 25)}%` }"
          />
        </div>

        <!-- Badges & trailing actions -->
        <div class="hidden items-center gap-2 sm:flex">
          <div class="h-5 w-20 border border-border/60 bg-secondary/60" />
          <div class="h-3 w-16 bg-secondary/50" />
        </div>
      </div>
    </div>

    <!-- Table View Skeleton -->
    <div
      v-else
      class="overflow-x-auto border border-border"
    >
      <table class="w-full text-left">
        <thead class="border-b border-border bg-secondary/40">
          <tr>
            <th class="p-3 w-10">
              <span class="sr-only">Pasirinkimas</span>
              <div class="size-4 bg-secondary/60" />
            </th>
            <th class="p-3">
              <span class="sr-only">Pavadinimas</span>
              <div class="h-4 w-32 bg-secondary" />
            </th>
            <th class="p-3 hidden sm:table-cell">
              <span class="sr-only">Kategorija</span>
              <div class="h-4 w-24 bg-secondary" />
            </th>
            <th class="p-3 hidden md:table-cell">
              <span class="sr-only">Data</span>
              <div class="h-4 w-28 bg-secondary" />
            </th>
            <th class="p-3">
              <span class="sr-only">Būsena</span>
              <div class="h-4 w-20 bg-secondary" />
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border bg-card">
          <tr
            v-for="n in rows"
            :key="n"
          >
            <td class="p-3">
              <div class="size-4 bg-secondary/40" />
            </td>
            <td class="p-3">
              <div
                class="h-4 bg-secondary"
                :style="{ width: `${50 + ((n * 11) % 40)}%` }"
              />
            </td>
            <td class="p-3 hidden sm:table-cell">
              <div class="h-4 w-20 bg-secondary/70" />
            </td>
            <td class="p-3 hidden md:table-cell">
              <div class="h-4 w-24 bg-secondary/60" />
            </td>
            <td class="p-3">
              <div class="h-5 w-16 border border-border/50 bg-secondary/60" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  viewMode?: 'rows' | 'table';
  rows?: number;
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'rows',
  rows: 5,
  class: undefined,
});
</script>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none;
  }
}
</style>
