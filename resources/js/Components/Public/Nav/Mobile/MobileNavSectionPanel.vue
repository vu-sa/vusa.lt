<template>
  <ul class="flex flex-col pb-2">
    <li v-for="(link, index) in flatLinks" :key="`${link.name}-${index}`">
      <MobileNavLinkRow :link @close="$emit('close')" />
    </li>
  </ul>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { NavItem } from '../types';

import MobileNavLinkRow from './MobileNavLinkRow.vue';

const props = defineProps<{
  item: NavItem;
}>();

defineEmits<{
  close: [];
}>();

// Mobile has no room for the desktop's multi-column layout — flatten every
// column into a single vertical list; `divider`/`category-link` rows still
// carry the visual grouping.
const flatLinks = computed(() => props.item.links.flat());
</script>
