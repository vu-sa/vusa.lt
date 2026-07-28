<template>
  <component :is="resolved" />
</template>

<script setup lang="ts">
/**
 * Resolves a CMS-stored icon name (e.g. saved from CardStackEditor / CarouselSlideDeckEditor)
 * to a bundled Fluent icon component. Public display components must not use a runtime
 * icon-fetching component (like @iconify/vue's <Icon>) since that hits a public API per
 * icon on every page load — this map is bundled and JIT-visible instead.
 */
import { computed } from 'vue';

import { CARD_ICON_MAP, DEFAULT_CARD_ICON } from './cardIcons';

const props = defineProps<{
  name?: string;
}>();

const resolved = computed(() => (props.name && CARD_ICON_MAP[props.name]) || DEFAULT_CARD_ICON);
</script>
