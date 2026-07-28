<template>
  <template v-for="(element, index) in content" :key="element.id">
    <!-- Async components with Suspense - wrapped in div for proper spacing classes -->
    <!-- Note: Suspense doesn't pass through class attributes, so we wrap in a div -->
    <!-- The skeleton's reserved height is bound to the fallback itself, not this wrapper,
         so it only occupies space while actually loading (not permanently afterwards). -->
    <div v-if="isAsyncComponent(element.type)" :class="blockClasses(element)">
      <Suspense>
        <template #default>
          <component :is="getComponentForType(element.type)" :element :html
            :is-first-element="index === 0"
            :anchor-id="element.id"
            :resolved="resolvedFor(element)"
            :prefetched-news="element.type === 'news' ? news : undefined"
            :prefetched-calendar="element.type === 'calendar' ? calendarEvents : undefined" />
        </template>
        <template #fallback>
          <component :is="getSkeletonComponent(element.type)" :class="getSkeletonForType(element.type).height" />
        </template>
      </Suspense>
    </div>

    <!-- Synchronous component rendering (tiptap only) -->
    <component :is="getComponentForType(element.type)" v-else :element :html
      :is-first-element="index === 0"
      :class="blockClasses(element)">
      <!-- Default slot for components that need content -->
      <template v-if="['shadcn-card'].includes(element.type)">
        <RichContentTiptapHTML v-if="!html" :json_content="element.json_content" />
        <div v-else v-html="element.html" />
      </template>
    </component>
  </template>
</template>

<script setup lang="ts">
import { markRaw } from 'vue';

import { blockLayoutClasses } from './blockLayout';
import { getContentType, getSkeletonForType } from './Types';

import { Skeleton } from '@/Components/ui/skeleton';
import type { NewsItem } from '@/Types/contentParts';
import RichContentTiptapHTML from './RichContentTiptapHTML.vue';

const props = defineProps<{
  content: models.ContentPart[];
  html?: boolean;
  class?: string;
  /** Server-resolved payloads keyed by content-part id (PublicController::resolveContentParts). */
  resolved?: Record<number, unknown>;
  /** @deprecated Superseded by `resolved` — only HomePage still supplies these directly. */
  news?: NewsItem[];
  calendarEvents?: Array<Record<string, unknown>>;
}>();

/**
 * Only types the registry declares `serverResolved` receive the `resolved` payload —
 * otherwise it would fall through as an undeclared prop on every other display and
 * stringify into the DOM (`resolved="[object Object]"`).
 */
function resolvedFor(element: models.ContentPart): unknown {
  if (!getContentType(element.type).serverResolved) return undefined;

  return props.resolved?.[element.id];
}

// Get display component for type — sourced from the registry so adding a type is a
// single registry entry instead of a switch case here plus one in ContentEditorFactory.
function getComponentForType(type: string) {
  return getContentType(type).display;
}

// Resolve a block's canvas column + flow classes. `options.width` lets an author override
// the type's registry default per-block (e.g. narrow a gallery to `content`). Shared with
// the editor's preview surfaces (ContentEditorFactory, BlockPickerDialog) via blockLayout.ts
// so a previewed block's width never disagrees with its public rendering.
const blockClasses = blockLayoutClasses;

// Only tiptap's display is loaded synchronously (see the registry); everything else
// needs a Suspense boundary.
function isAsyncComponent(type: string): boolean {
  return type !== 'tiptap';
}

// Build the Suspense fallback component for a type from its registry skeleton.
// Cached so repeated blocks of the same type share one component definition.
const skeletonCache = new Map<string, { components: { Skeleton: typeof Skeleton }; template: string }>();
function getSkeletonComponent(type: string) {
  let cached = skeletonCache.get(type);
  if (!cached) {
    cached = markRaw({ components: { Skeleton }, template: getSkeletonForType(type).template });
    skeletonCache.set(type, cached);
  }
  return cached;
}
</script>
