<template>
  <div
    ref="el"
    :data-column="index"
    class="flex min-w-0 min-h-[3rem] flex-col gap-1.5 rounded-md border border-dashed border-transparent p-1 transition-colors"
    :class="[isEmpty && 'border-zinc-200 dark:border-zinc-700']"
  >
    <NavigationLinkCard
      v-for="link in links"
      :key="link.id"
      :link
      @toggle-active="val => $emit('toggle-active', link, val)"
      @delete="$emit('delete', link)"
    />
    <p v-if="isEmpty" class="p-2 text-center text-xs text-muted-foreground">
      {{ $t('navigation.builder.empty_column') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import NavigationLinkCard from './NavigationLinkCard.vue';

import type { AdminNavigationLink } from './types';

const props = defineProps<{
  links: AdminNavigationLink[];
  index: number;
}>();

defineEmits<{
  (event: 'toggle-active', link: AdminNavigationLink, value: boolean): void;
  (event: 'delete', link: AdminNavigationLink): void;
}>();

// `min-w-0` is what keeps the builder inside the viewport on a phone. This div is a
// grid item in NavigationRootItem's column grid, so its automatic minimum size is
// `min-content` — and a link card's min-content is wide, because the name and URL
// inside it are `truncate`d (`white-space: nowrap`), which caps how narrow the card
// *renders* but not what it *contributes*. The auto track therefore grew to the widest
// card (~390px against a ~356px container) and pushed the column — most visibly a
// full-height card's background image — off the right edge. Zeroing the minimum lets
// the track shrink to the container and the cards' own `truncate` take over.
//
// The drag surface itself is registered one level up, in NavigationRootItem — moving
// a link between two columns means splicing two *different* columns' arrays at once,
// which needs a scope that can see all of a root's columns together, not just this one.
// SortableJS's `draggable: '.nav-link-card'` option (set by the parent) restricts which
// children it treats as draggable items, so the "empty column" placeholder text below
// never gets picked up as something to drag.
const el = ref<HTMLElement | null>(null);
const isEmpty = computed(() => props.links.length === 0);

defineExpose({ el });
</script>
