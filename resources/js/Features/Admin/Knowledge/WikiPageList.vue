<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';

import type { WikiPageSummary } from '@/Types/learning';

withDefaults(defineProps<{
  pages: WikiPageSummary[];
  /** Section titles by page id, shown as a quiet tag when the list mixes sections. */
  sectionOf?: (page: WikiPageSummary) => string | null;
  dense?: boolean;
  preview?: boolean;
  /** Articles the viewer has ticked as learned. */
  doneIds?: ReadonlySet<string>;
}>(), { sectionOf: undefined, dense: false, preview: false, doneIds: undefined });
</script>

<template>
  <ul class="divide-y divide-border border-y border-border" data-slot="wiki-page-list">
    <li v-for="page in pages" :key="page.id">
      <Link :href="page.url" :data-knowledge-id="page.id" :aria-haspopup="preview ? 'dialog' : undefined" :class="['group block', dense ? 'py-2.5' : 'py-3.5']">
        <span class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
          <span class="font-semibold text-foreground underline decoration-transparent underline-offset-4 transition-colors group-hover:decoration-foreground">{{ page.title }}</span>
          <Check v-if="doneIds?.has(page.id)" class="size-3.5 shrink-0 self-center stroke-3 text-status-success" role="img" :aria-label="$t('learning.wiki.learned')" data-slot="wiki-page-learned" />
          <span v-if="page.abbreviation" class="text-xs font-bold tracking-wide text-muted-foreground">{{ page.abbreviation }}</span>
          <span v-if="sectionOf?.(page)" class="ml-auto text-xs text-muted-foreground">{{ sectionOf(page) }}</span>
        </span>
        <span v-if="page.summary && !dense" class="mt-1 line-clamp-2 block max-w-prose text-sm leading-relaxed text-muted-foreground">{{ page.summary }}</span>
      </Link>
    </li>
  </ul>
</template>
