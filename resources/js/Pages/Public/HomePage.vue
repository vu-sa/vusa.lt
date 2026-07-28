<template>
  <!-- <SummerCamps v-if="$page.props.app.locale === 'lt'" /> -->
  <Head v-if="firstNewsImageUrl">
    <link rel="preload" as="image" :href="firstNewsImageUrl" fetchpriority="high">
  </Head>
  <!-- RichContentParser renders a v-for fragment (no single root), so its per-block
       rc-content/rc-wide/rc-full/rc-flush classes only mean anything inside an
       ancestor .rc-canvas grid — this provides that grid. -->
  <div class="rc-canvas" style="--rc-measure: 44rem">
    <RichContentParser :content="(content as ContentWithParts)?.parts ?? []" :resolved="resolvedParts" :news :calendar-events />
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import RichContentParser from '@/Components/RichContent/RichContentParser.vue';
import type { NewsItem } from '@/Types/contentParts';

// Type for content with parts
interface ContentWithParts {
  parts?: models.ContentPart[];
  [key: string]: unknown;
}

defineProps<{
  content: ContentWithParts | null;
  /** Server-resolved dynamic blocks (link-list, event-list, …) keyed by content-part id. */
  resolvedParts?: Record<number, unknown>;
  news?: NewsItem[];
  calendarEvents?: Array<Record<string, unknown>>;
  firstNewsImageUrl?: string | null;
}>();

// Home page doesn't need breadcrumbs - they're cleared by PublicLayout

// const SummerCamps = defineAsyncComponent(
//  // eslint-disable-next-line no-secrets/no-secrets
//  () => import("@/Components/Public/FullWidth/SummerCamps.vue"),
// );
</script>
