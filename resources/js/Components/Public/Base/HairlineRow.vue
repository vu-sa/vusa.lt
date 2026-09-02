<template>
  <component :is="as" data-slot="hairline-row">
    <component
      :is="href ? 'a' : 'div'"
      :href
      :class="cn(
        'group flex w-full items-center gap-5 py-5 text-left',
        href && 'transition-colors hover:bg-secondary/60',
        props.class,
      )"
    >
      <div v-if="$slots.leading" class="shrink-0">
        <slot name="leading" />
      </div>

      <div class="min-w-0 flex-1">
        <p v-if="eyebrow || $slots.eyebrow" class="u-eyebrow mb-1.5">
          <slot name="eyebrow">
            {{ eyebrow }}
          </slot>
        </p>
        <p v-if="title || $slots.default" class="font-bold text-foreground group-hover:text-brand">
          <slot>{{ title }}</slot>
        </p>
        <p v-if="meta || $slots.meta" class="mt-1 text-sm text-muted-foreground">
          <slot name="meta">
            {{ meta }}
          </slot>
        </p>
      </div>

      <div v-if="$slots.trailing" class="shrink-0 text-muted-foreground">
        <slot name="trailing" />
      </div>
    </component>
  </component>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * One row of a `HairlineList`: optional leading block (a `DatePlate`, thumbnail or icon),
 * eyebrow + title + meta, optional trailing affordance.
 *
 * The separator belongs to the list, not the row, so a row can also be used on its own without
 * bringing a stray border with it.
 */
const props = withDefaults(defineProps<{
  title?: string;
  eyebrow?: string;
  meta?: string;
  /** Makes the whole row a link. A resolved URL — this tier never calls `route()`. */
  href?: string;
  as?: string;
  class?: HTMLAttributes['class'];
}>(), {
  title: undefined,
  eyebrow: undefined,
  meta: undefined,
  href: undefined,
  as: 'div',
  class: undefined,
});
</script>
