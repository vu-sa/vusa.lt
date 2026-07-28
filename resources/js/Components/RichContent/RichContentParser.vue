<template>
  <template v-for="(group, index) in groupedContent" :key="group.element.id ?? index">
    <!-- `section` marker — wraps every part collected into `group.children` (see
         `groupedContent`) inside a real `<section>`, until the next section marker or
         the end of the content. Rendered directly (not through the generic
         `getComponentForType` dispatch every other type uses) because it needs a
         slot of child blocks, not just `element`/`html`/`resolved` props. -->
    <SectionDisplay
      v-if="group.kind === 'section'"
      :element="(group.element as unknown as Section)"
      :anchor-id="group.element.id"
      :has-children="group.children.length > 0"
      :class="blockClasses(group.element)"
    >
      <RichContentBlock
        v-for="(child, childIndex) in group.children" :key="child.id ?? childIndex"
        :element="child" :html
        :is-first-element="false"
        :resolved="resolvedFor(child)"
        :news="child.type === 'news' ? news : undefined"
        :calendar-events="child.type === 'calendar' ? calendarEvents : undefined"
      />
    </SectionDisplay>

    <RichContentBlock
      v-else
      :element="group.element" :html
      :is-first-element="index === 0"
      :resolved="resolvedFor(group.element)"
      :news="group.element.type === 'news' ? news : undefined"
      :calendar-events="group.element.type === 'calendar' ? calendarEvents : undefined"
    />
  </template>
</template>

<script setup lang="ts">
/**
 * Renders a `Content`'s ordered parts. Two responsibilities live here rather than in
 * `RichContentBlock`: looking up each part's server-resolved payload (`resolvedFor`),
 * and grouping parts around `section` markers (`groupedContent`) — both need the
 * *whole* parts array/resolved map, not just one element.
 */
import { computed } from 'vue';

import { getContentType } from './Types';
import { blockLayoutClasses } from './blockLayout';
import RichContentBlock from './RichContentBlock.vue';
import SectionDisplay from './RCSection/SectionDisplay.vue';
import type { NewsItem, Section } from '@/Types/contentParts';

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

// Resolve a block's canvas column + flow classes. `options.width` lets an author override
// the type's registry default per-block (e.g. narrow a gallery to `content`). Shared with
// the editor's preview surfaces (ContentEditorFactory, BlockPickerDialog) via blockLayout.ts
// so a previewed block's width never disagrees with its public rendering.
const blockClasses = blockLayoutClasses;

type ContentGroup =
  | { kind: 'block'; element: models.ContentPart }
  | { kind: 'section'; element: models.ContentPart; children: models.ContentPart[] };

/**
 * Splits `content` into top-level render groups: a plain block, or a `section` marker
 * plus every part that follows it up to the next `section` marker (or the end).
 * `options.wraps: 'none'` makes a section render header-only — it still opens a group
 * (so it gets its own chrome/anchor), but doesn't absorb anything after it; the very
 * next element (section or not) starts fresh, exactly as if this section didn't exist
 * for grouping purposes. This is also what makes a `wraps: 'none'` section act as an
 * implicit terminator for whatever section came before it — a new section element
 * always ends the previous group regardless of its own `wraps` value.
 */
const groupedContent = computed<ContentGroup[]>(() => {
  const groups: ContentGroup[] = [];
  let active: { kind: 'section'; element: models.ContentPart; children: models.ContentPart[] } | null = null;

  for (const element of props.content) {
    if (element.type === 'section') {
      const group: ContentGroup = { kind: 'section', element, children: [] };
      groups.push(group);
      active = element.options?.wraps === 'none' ? null : (group as typeof active);
      continue;
    }

    if (active) {
      active.children.push(element);
    } else {
      groups.push({ kind: 'block', element });
    }
  }

  return groups;
});
</script>
