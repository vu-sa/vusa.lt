<template>
  <Link
    :href
    :class="cn(
      'group flex items-center gap-2.5 rounded-lg border border-border bg-card px-2.5 py-2',
      interactiveCardClass,
      props.class,
    )"
    data-slot="entity-link-card"
  >
    <!-- Leading tile: an icon by default, but callers swap in a DateBadge, avatar, etc. -->
    <slot name="leading">
      <div
        v-if="icon"
        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted"
      >
        <component :is="icon" class="h-5 w-5 text-muted-foreground" />
      </div>
    </slot>

    <div class="min-w-0 flex-1 leading-tight">
      <p
        v-if="eyebrow"
        class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground"
      >
        {{ eyebrow }}
      </p>
      <p class="truncate text-sm font-medium text-foreground group-hover:text-primary">
        <slot name="title">
          {{ title }}
        </slot>
      </p>
      <p v-if="subtitle" class="truncate text-xs text-muted-foreground">
        {{ subtitle }}
      </p>
    </div>

    <slot name="trailing">
      <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground group-hover:text-primary" />
    </slot>
  </Link>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

import { cn } from '@/Utils/Shadcn/utils';
import { interactiveCardClass } from '@/Utils/interactiveCard';

const props = withDefaults(defineProps<{
  /** Resolved URL — callers pass `route(...)`, keeping this component route-agnostic. */
  href: string;
  /** Small uppercase label above the title, e.g. "Institucija" or "Kitas posėdis". */
  eyebrow?: string;
  title?: string;
  subtitle?: string;
  icon?: Component;
  class?: HTMLAttributes['class'];
}>(), {
  eyebrow: undefined,
  title: undefined,
  subtitle: undefined,
  icon: undefined,
  class: undefined,
});
</script>
