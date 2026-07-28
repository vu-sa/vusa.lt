<template>
  <!-- Async components with Suspense - wrapped in div for proper spacing classes -->
  <!-- Note: Suspense doesn't pass through class attributes, so we wrap in a div -->
  <!-- The skeleton's reserved height is bound to the fallback itself, not this wrapper,
       so it only occupies space while actually loading (not permanently afterwards). -->
  <div v-if="isAsyncComponent" :class="blockClasses">
    <Suspense>
      <template #default>
        <component :is="displayComponent" :element :html
          :is-first-element="isFirstElement"
          :anchor-id="element.id"
          :resolved="resolved"
          :prefetched-news="element.type === 'news' ? news : undefined"
          :prefetched-calendar="element.type === 'calendar' ? calendarEvents : undefined" />
      </template>
      <template #fallback>
        <component :is="skeletonComponent" :class="skeleton.height" />
      </template>
    </Suspense>
  </div>

  <!-- Synchronous component rendering (tiptap only) -->
  <component :is="displayComponent" v-else :element :html
    :is-first-element="isFirstElement"
    :class="blockClasses">
    <!-- Default slot for components that need content -->
    <template v-if="element.type === 'shadcn-card'">
      <RichContentTiptapHTML v-if="!html" :json_content="element.json_content" />
      <div v-else v-html="element.html" />
    </template>
  </component>
</template>

<script setup lang="ts">
/**
 * Renders a single content part: async types get a Suspense boundary + skeleton
 * fallback, `tiptap` renders synchronously (the common case). Extracted out of
 * RichContentParser.vue so a `section` block can render its wrapped children through
 * the exact same per-block logic (Suspense, skeleton, width classes) as top-level
 * blocks — nothing about how an individual block renders should differ depending on
 * whether it happens to sit inside a section.
 */
import { computed } from 'vue';

import { blockLayoutClasses } from './blockLayout';
import { getContentType, getSkeletonForType } from './Types';
import { getSkeletonComponent } from './skeletonComponents';
import type { NewsItem } from '@/Types/contentParts';
import RichContentTiptapHTML from './RichContentTiptapHTML.vue';

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  isFirstElement?: boolean;
  /** This block's already-looked-up server-resolved payload (see RichContentParser's `resolvedFor`). */
  resolved?: unknown;
  /** @deprecated Superseded by `resolved` — only HomePage still supplies these directly. */
  news?: NewsItem[];
  calendarEvents?: Array<Record<string, unknown>>;
}>();

const displayComponent = computed(() => getContentType(props.element.type).display);
const blockClasses = computed(() => blockLayoutClasses(props.element));
const skeleton = computed(() => getSkeletonForType(props.element.type));
const skeletonComponent = computed(() => getSkeletonComponent(props.element.type));

// Only tiptap's display is loaded synchronously (see the registry); everything else
// needs a Suspense boundary.
const isAsyncComponent = computed(() => props.element.type !== 'tiptap');
</script>
