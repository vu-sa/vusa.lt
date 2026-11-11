<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useResizeObserver } from '@vueuse/core';
import { Link } from '@inertiajs/vue3';
import { Check, FileText } from '@lucide/vue';

import type { WikiMaterial } from '@/Types/learning';

// One row that scrolls sideways, so 14 other units' regulations never push the page down;
// the viewer's own units come first (the presenter sorts them).
defineProps<{ materials: WikiMaterial[]; doneIds?: ReadonlySet<string> }>();
const failed = reactive(new Set<string>());

// Fade whichever edge still has tiles beyond it, so a row that scrolls reads as one.
const strip = ref<HTMLElement | null>(null);
const more = reactive({ left: false, right: false });
function measure(): void {
  const el = strip.value;
  if (!el) return;
  more.left = el.scrollLeft > 1;
  more.right = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
}
onMounted(measure);
useResizeObserver(strip, measure);
const fade = '2.5rem';
const maskImage = computed(() => (more.left || more.right)
  ? `linear-gradient(to right, ${more.left ? 'transparent' : 'black'} 0, black ${more.left ? fade : '0'}, black calc(100% - ${more.right ? fade : '0px'}), ${more.right ? 'transparent' : 'black'} 100%)`
  : undefined);
</script>

<template>
  <ul
    ref="strip"
    class="-mx-1 flex snap-x snap-mandatory scroll-px-1 gap-3 overflow-x-auto px-1 pb-3"
    :style="{ maskImage, WebkitMaskImage: maskImage }"
    :data-more-left="more.left || undefined"
    :data-more-right="more.right || undefined"
    data-slot="wiki-materials"
    @scroll.passive="measure"
  >
    <li v-for="material in materials" :key="material.id" class="w-28 shrink-0 snap-start sm:w-32" :data-mine="material.mine || undefined">
      <Link :href="material.url" class="group block outline-none" data-slot="wiki-material">
        <span
          :class="[
            'flex aspect-3/4 w-full items-center justify-center overflow-hidden border bg-muted transition-colors',
            material.mine ? 'border-foreground' : 'border-border',
            'group-hover:border-brand group-focus-visible:border-brand',
          ]"
        >
          <img
            v-if="material.thumbnailUrl && !failed.has(material.id)"
            :src="material.thumbnailUrl"
            alt=""
            loading="lazy"
            class="size-full object-cover object-top"
            @error="failed.add(material.id)"
          >
          <FileText v-else class="size-8 text-muted-foreground" aria-hidden="true" />
        </span>
        <span class="mt-2 flex items-start gap-1 text-sm font-semibold leading-snug text-foreground group-hover:text-brand">
          <span class="line-clamp-2">{{ material.title }}</span>
          <Check v-if="doneIds?.has(material.id)" class="mt-0.5 size-3.5 shrink-0 stroke-3 text-status-success" role="img" :aria-label="$t('learning.wiki.learned')" />
        </span>
        <span v-if="material.tenant" class="mt-0.5 block text-xs text-muted-foreground">{{ material.tenant }}</span>
      </Link>
    </li>
  </ul>
</template>
