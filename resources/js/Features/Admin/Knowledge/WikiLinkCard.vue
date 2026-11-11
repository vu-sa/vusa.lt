<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

import type { WikiPageSummary } from '@/Types/learning';

const props = defineProps<{ page: WikiPageSummary }>();
// The summary names the article itself, in bold, so the title is shown only when there is no summary.
const imageFailed = ref(false);
watch(() => props.page.id, () => {
  imageFailed.value = false;
});
const showAbbreviation = computed(() => props.page.abbreviation !== '' && !props.page.summary.includes(props.page.abbreviation));
</script>

<template>
  <Link
    :href="page.url"
    class="group block outline-none transition-colors hover:bg-accent/60 focus-visible:bg-accent/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset"
    data-slot="wiki-link-card"
  >
    <span class="flex items-start gap-4 p-4">
      <span class="min-w-0 flex-1 space-y-2">
        <!-- Sanitized on write to bold and italic marks (HtmlSanitizerService::sanitizeInlineMarks). -->
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-if="page.summaryHtml" class="block text-sm leading-relaxed text-foreground [&_b]:font-bold [&_strong]:font-bold" data-slot="wiki-link-card-summary" v-html="page.summaryHtml" />
        <span v-else class="block text-sm font-bold leading-snug text-foreground">{{ page.title }}</span>
        <abbr v-if="showAbbreviation" :title="page.title" class="inline-block border border-border px-1.5 py-px text-xs font-bold tracking-wide no-underline">{{ page.abbreviation }}</abbr>
      </span>
      <!-- A document's first page, as Wikipedia's preview shows the lead image beside the text. -->
      <img
        v-if="page.thumbnailUrl && !imageFailed"
        :src="page.thumbnailUrl"
        alt=""
        loading="lazy"
        class="aspect-3/4 w-20 shrink-0 border border-border bg-muted object-cover object-top"
        data-slot="wiki-link-card-image"
        @error="imageFailed = true"
      >
    </span>
    <span class="flex min-h-11 items-center justify-between gap-3 border-t border-border px-4 text-xs text-muted-foreground sm:min-h-9">
      <span>{{ $t(`learning.kinds.${page.kind}`) }}</span>
      <span class="inline-flex items-center gap-1 font-semibold text-foreground group-hover:text-brand">
        {{ $t('learning.wiki.read') }}<ArrowRight class="size-3.5" aria-hidden="true" />
      </span>
    </span>
  </Link>
</template>
