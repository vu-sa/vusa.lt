<template>
  <RuledGrid as="ul" :columns="{ base: 2, lg: columns }" top-rule="cells" data-slot="navigation-tiles">
    <li v-for="item in items" :key="item.key" class="flex">
      <Link
        :href="item.href"
        prefetch
        :class="[
          'group flex w-full items-start gap-3 p-4 text-left sm:p-5',
          'transition-colors hover:bg-secondary',
          'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
        ]"
        :data-tile="item.key"
        @click="emit('navigate', item.key)"
      >
        <component :is="item.icon" class="size-5 shrink-0 text-brand" aria-hidden="true" />
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-bold text-foreground" data-tile-label>{{ item.label }}</span>
          <span v-if="item.description" class="mt-1 block text-xs text-pretty text-muted-foreground" data-tile-description>
            {{ item.description }}
          </span>
        </span>
        <ArrowRight
          :class="[
            'mt-0.5 size-4 shrink-0 -translate-x-1 text-muted-foreground opacity-0 transition-all',
            'group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100',
          ]"
          aria-hidden="true"
        />
      </Link>
    </li>
  </RuledGrid>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import type { Component } from 'vue';

import { RuledGrid } from '@/Components/Brand';

export interface NavigationTileItem {
  key: string;
  /** Resolved URL — callers pass `route(...)`. */
  href: string;
  label: string;
  description?: string | null;
  icon: Component;
}

withDefaults(defineProps<{
  items: NavigationTileItem[];
  columns?: 2 | 3 | 4;
}>(), {
  columns: 4,
});

const emit = defineEmits<{
  navigate: [key: string];
}>();
</script>
