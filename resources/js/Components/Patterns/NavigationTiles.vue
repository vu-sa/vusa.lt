<template>
  <!-- Each tile draws all four rules and overlaps its neighbours by 1px, so lines exist only where
       a tile does — a container-level rule would run on past a short row's last tile. -->
  <ul :class="['grid grid-cols-2 pt-px pl-px', columnsClass]" data-slot="navigation-tiles">
    <li v-for="item in items" :key="item.key" class="-mt-px -ml-px flex border border-border">
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
  </ul>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

export interface NavigationTileItem {
  key: string;
  /** Resolved URL — callers pass `route(...)`. */
  href: string;
  label: string;
  description?: string | null;
  icon: Component;
}

const props = withDefaults(defineProps<{
  items: NavigationTileItem[];
  columns?: 2 | 3 | 4;
}>(), {
  columns: 4,
});

const emit = defineEmits<{
  navigate: [key: string];
}>();

const columnsClass = computed(() => ({ 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4' })[props.columns]);
</script>
