<template>
  <article :class="cn('flex h-full min-w-0 flex-col overflow-y-auto', props.class)" data-slot="preview-pane">
    <header class="border-b border-border p-5">
      <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
          <slot name="mark">
            <EntityTypeMark v-if="entityType" :type="entityType" icon-only />
          </slot>
          <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
            {{ eyebrow }}
          </p>
          <slot name="badges" />
        </div>
        <div v-if="$slots.status" class="shrink-0">
          <slot name="status" />
        </div>
      </div>

      <div class="mt-2 flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg font-semibold leading-snug text-foreground">
            <Link
              v-if="href"
              :href
              class="hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
              <slot name="title">
                {{ title }}
              </slot>
            </Link>
            <slot v-else name="title">
              {{ title }}
            </slot>
          </h2>
          <div v-if="subtitle || $slots.subtitle" class="mt-1 text-sm text-muted-foreground">
            <slot name="subtitle">
              {{ subtitle }}
            </slot>
          </div>
        </div>
        <div v-if="$slots.media" class="shrink-0">
          <slot name="media" />
        </div>
      </div>

      <div v-if="$slots.actions" class="mt-4 flex flex-wrap items-center gap-2" data-slot="preview-actions">
        <slot name="actions" />
      </div>
    </header>

    <div v-if="$slots.default" class="flex flex-1 flex-col gap-6 p-5">
      <slot />
    </div>
  </article>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { cn } from '@/Utils/Shadcn/utils';

/**
 * One record at a glance, beside a list (collection Peržiūra, pickers, search). It mirrors a
 * record page's identity band and facts, never its relation editing, tabs or discussion.
 */
const props = withDefaults(defineProps<{
  eyebrow: string;
  title?: string;
  /** Makes the title open the canonical page. */
  href?: string;
  subtitle?: string;
  /** Key from `Constants/entityTypes`; shows the entity's mark before the eyebrow. */
  entityType?: string;
  class?: HTMLAttributes['class'];
}>(), {
  title: '',
  href: undefined,
  subtitle: undefined,
  entityType: undefined,
  class: undefined,
});
</script>
