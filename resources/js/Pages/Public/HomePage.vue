<template>
  <!-- RichContentParser renders a v-for fragment (no single root), so its per-block
       rc-content/rc-wide/rc-full/rc-flush classes only mean anything inside an
       ancestor .rc-canvas grid — this provides that grid. -->
  <div class="rc-canvas" style="--rc-measure: 44rem">
    <h1 class="sr-only">
      {{ heading }}
    </h1>
    <RichContentParser :content="(content as ContentWithParts)?.parts ?? []" :resolved="resolvedParts" />
  </div>
</template>

<script setup lang="ts">
import RichContentParser from '@/Components/RichContent/RichContentParser.vue';
import { providePageHeading } from '@/Composables/usePageHeading';

// Type for content with parts
interface ContentWithParts {
  parts?: models.ContentPart[];
  [key: string]: unknown;
}

defineProps<{
  content: ContentWithParts | null;
  /** Server-resolved dynamic blocks (link-list, event-list, …) keyed by content-part id. */
  resolvedParts?: Record<number, unknown>;
  /** The tenant's name; heroes below are titled sections, not the page's subject. */
  heading: string;
}>();

providePageHeading();

// Home page doesn't need breadcrumbs - they're cleared by PublicLayout
</script>
