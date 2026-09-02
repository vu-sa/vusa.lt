<template>
  <SectionBand :spacing divider="bottom" :class="props.class" data-slot="page-title-band">
    <div class="flex flex-wrap items-end justify-between gap-x-10 gap-y-6">
      <DisplayHeading
        :title
        :eyebrow
        :lead
        :size
        as="h1"
        class="min-w-0 flex-1"
      >
        <slot />
        <template v-if="$slots.eyebrow" #eyebrow>
          <slot name="eyebrow" />
        </template>
        <template v-if="$slots.lead" #lead>
          <slot name="lead" />
        </template>
      </DisplayHeading>

      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </div>
  </SectionBand>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import DisplayHeading from './DisplayHeading.vue';
import SectionBand from './SectionBand.vue';

/**
 * The band every listing and detail page opens with: eyebrow, ruled `h1`, lead, and an optional
 * action on the right, closed off by a hairline.
 *
 * Breadcrumbs are deliberately not part of this — `PublicLayout` renders them above the page
 * content for every route, so a page that also drew its own would show two trails.
 */
const props = withDefaults(defineProps<{
  title?: string;
  eyebrow?: string;
  lead?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  spacing?: 'none' | 'tight' | 'default' | 'loose';
  class?: HTMLAttributes['class'];
}>(), {
  title: undefined,
  eyebrow: undefined,
  lead: undefined,
  size: 'lg',
  spacing: 'default',
  class: undefined,
});
</script>
