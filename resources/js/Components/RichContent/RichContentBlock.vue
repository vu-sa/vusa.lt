<template>
  <!-- Async components with Suspense - wrapped in div for proper spacing classes -->
  <!-- Note: Suspense doesn't pass through class attributes, so we wrap in a div -->
  <!-- The skeleton's reserved height is bound to the fallback itself, not this wrapper,
       so it only occupies space while actually loading (not permanently afterwards). -->
  <div v-if="isAsyncComponent" :class="blockClasses">
    <Suspense>
      <template #default>
        <component :is="displayComponent" :element :html
          :is-first-element
          :anchor-id="element.id"
          :resolved
          :band
          :prefetched-news="element.type === 'news' ? news : undefined"
          :prefetched-calendar="element.type === 'calendar' ? calendarEvents : undefined">
          <!-- shadcn-card renders its body through a default slot. This must be provided
               for both public pages and the editor's global preview; otherwise the card
               body appears empty even though json_content is present. -->
          <template v-if="element.type === 'shadcn-card'">
            <RichContentTiptapHTML v-if="!html" :json_content="element.json_content" />
            <div v-else v-html="element.html" />
          </template>
        </component>
      </template>
      <template #fallback>
        <component :is="skeletonComponent" :class="skeleton.height" />
      </template>
    </Suspense>
  </div>

  <!-- Synchronous component rendering (tiptap only). Prefer the server-rendered HTML
       when it exists; fall back to live json_content so unsaved editor blocks still
       preview instead of rendering blank. -->
  <component :is="displayComponent" v-else-if="element.html !== undefined" :element :html
    :is-first-element
    :class="blockClasses" />
  <component :is="RichContentTiptapHTML" v-else :json_content="element.json_content"
    :class="blockClasses" />
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
import RichContentTiptapHTML from './RichContentTiptapHTML.vue';
import type { BandResolution } from './bandLayout';

import type { NewsItem } from '@/Types/contentParts';

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  isFirstElement?: boolean;
  /** This block's already-looked-up server-resolved payload (see RichContentParser's `resolvedFor`). */
  resolved?: unknown;
  /** This block's already-looked-up band chrome (see RichContentParser's `bandFor`). */
  band?: BandResolution;
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
