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
      :band="bandFor(group.element)"
      :class="blockClasses(group.element)"
    >
      <RichContentBlock
        v-for="(child, childIndex) in group.children" :key="child.id ?? childIndex"
        :element="child" :html
        :resolved="resolvedFor(child)"
        :band="bandFor(child)"
      />
    </SectionDisplay>

    <RichContentBlock
      v-else
      :element="group.element" :html
      :resolved="resolvedFor(group.element)"
      :band="bandFor(group.element)"
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

import { groupContent } from './groupContent';
import { getDisplayType } from './Types/display';
import { blockLayoutClasses } from './blockLayout';
import { endsSectionWrapping, resolveBandRole, resolveBands, type BandResolution } from './bandLayout';
import RichContentBlock from './RichContentBlock.vue';
import SectionDisplay from './RCSection/SectionDisplay.vue';

import type { Section } from '@/Types/contentParts';

const props = defineProps<{
  content: models.ContentPart[];
  html?: boolean;
  class?: string;
  /** Server-resolved payloads keyed by content-part id (PublicController::resolveContentParts). */
  resolved?: Record<number, unknown>;
}>();

/**
 * Only types the registry declares `serverResolved` receive the `resolved` payload —
 * otherwise it would fall through as an undeclared prop on every other display and
 * stringify into the DOM (`resolved="[object Object]"`).
 */
function resolvedFor(element: models.ContentPart): unknown {
  if (!getDisplayType(element.type).serverResolved) return undefined;

  return props.resolved?.[element.id];
}

// Resolve a block's canvas column + flow classes. `options.width` lets an author override
// the type's registry default per-block (e.g. narrow a gallery to `content`). Shared with
// the editor's preview surfaces (ContentEditorFactory, BlockPickerDialog) via blockLayout.ts
// so a previewed block's width never disagrees with its public rendering.
const blockClasses = blockLayoutClasses;

// One alternation pass over the whole document — see bandLayout.ts. Threaded down to
// every band-capable display the same way `resolved` is (below): only a type that
// declares `bandRole` receives a real value, so an undeclared object prop never falls
// through and stringifies into the DOM on a display that doesn't ask for it.
const bandMap = computed<Map<models.ContentPart, BandResolution>>(() => resolveBands(props.content));

function bandFor(element: models.ContentPart): BandResolution | undefined {
  if (resolveBandRole(element.type, element.options) === 'flow') return undefined;

  return bandMap.value.get(element);
}

const groupedContent = computed(() => groupContent(props.content));
</script>
