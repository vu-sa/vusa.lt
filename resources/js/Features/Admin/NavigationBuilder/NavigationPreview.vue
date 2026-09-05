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
        <!-- `data-surface="public"` for the same reason the block previews carry it: this renders
             the public site's own menu component, so it should resolve the public palette and
             radius scale rather than admin's. -->
        <div data-surface="public" class="inline-block border shadow-sm font-public">
          <MainNavigationMenuContent :item="toPreviewItem(root)" is-used-without-root are-links-disabled />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';

import { toPreviewItem } from './toPreviewItem';

import type { AdminNavigationRoot } from './types';

defineProps<{
  roots: AdminNavigationRoot[];
}>();
</script>
