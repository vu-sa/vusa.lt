<template>
  <div class="flex flex-col gap-6">
    <div v-for="root in roots" :key="root.id">
      <p class="mb-2 text-sm font-semibold text-muted-foreground">
        {{ root.name || `#${root.id}` }}
      </p>
      <!-- The dropdown renders at the public site's own fixed widths (340px on narrow
           viewports, up to 800px on xl — see `resolveDropdownWidth`), which is wider
           than the admin content area on a phone. Scroll it inside its own container
           rather than letting it push the whole page sideways. -->
      <div class="overflow-x-auto">
        <div class="inline-block border">
          <MainNavigationMenuContent :item="toPreviewItem(root)" is-used-without-root are-links-disabled />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

import { toPreviewItem } from './toPreviewItem';
import type { AdminNavigationRoot } from './types';

import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';

defineProps<{
  roots: AdminNavigationRoot[];
}>();
</script>
