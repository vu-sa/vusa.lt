<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

import type { WikiPageSummary } from '@/Types/learning';

defineProps<{ page: WikiPageSummary }>();
</script>

<template>
  <div class="space-y-2 p-4" data-slot="wiki-link-card">
    <p class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
      <span class="text-base font-bold leading-snug text-foreground">{{ page.title }}</span>
      <abbr v-if="page.abbreviation" :title="page.title" class="border border-border px-1.5 py-px text-xs font-bold tracking-wide no-underline">{{ page.abbreviation }}</abbr>
    </p>
    <p v-if="page.summary" class="text-sm leading-relaxed text-muted-foreground">{{ page.summary }}</p>
    <Link :href="page.url" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-foreground underline decoration-border underline-offset-4 hover:decoration-foreground sm:min-h-0">
      {{ $t('learning.wiki.open_article') }}<ArrowRight class="size-3.5" aria-hidden="true" />
    </Link>
  </div>
</template>
