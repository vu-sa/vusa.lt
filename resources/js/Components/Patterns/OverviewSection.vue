<template>
  <!-- Inside an OverviewPage an empty section moves to the page's status list; elsewhere it
       collapses to one line (visual budget 7). -->
  <section v-if="!(empty && statusRegistry)" class="flex flex-col gap-3" data-slot="overview-section">
    <p v-if="empty" :class="['flex items-center gap-2 text-sm text-muted-foreground', variant === 'home' ? 'border-b border-border pb-3' : 'border-t border-border pt-3']">
      <component :is="icon" v-if="icon" class="size-4 shrink-0 text-brand" aria-hidden="true" />
      <span>
        <span :class="variant === 'home' ? 'font-bold uppercase tracking-[0.18em] text-foreground' : 'font-medium text-foreground'">{{ title }}</span>
        <span v-if="emptyText"> — {{ emptyText }}</span>
      </span>
    </p>

    <template v-else>
      <header :class="['flex items-center justify-between gap-4', headerRuleClass]">
        <h2 :class="['flex items-center gap-2 text-foreground', variant === 'home' ? 'text-sm font-bold uppercase tracking-[0.18em]' : 'text-base font-semibold']">
          <component :is="icon" v-if="icon" class="size-4 shrink-0 text-brand" aria-hidden="true" />
          {{ title }}
        </h2>
        <Link
          v-if="href"
          :href
          :class="variant === 'home'
            ? 'shrink-0 text-sm font-semibold text-brand hover:text-foreground'
            : 'text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline'"
        >
          {{ hrefLabel }}
        </Link>
        <div v-else-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
          <slot name="actions" />
        </div>
      </header>
      <slot />
    </template>
  </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, inject, onBeforeUnmount, useId, watchEffect, type Component } from 'vue';

import { OVERVIEW_STATUS_KEY } from './overviewStatus';

const props = withDefaults(defineProps<{
  title: string;
  icon?: Component;
  variant?: 'default' | 'home';
  empty?: boolean;
  emptyText?: string;
  href?: string;
  hrefLabel?: string;
  /** The content draws its own rules (a bordered list, tiles), so the heading drops its hairline. */
  contentRuled?: boolean;
}>(), {
  icon: undefined,
  variant: 'default',
  emptyText: undefined,
  href: undefined,
  hrefLabel: undefined,
});

const headerRuleClass = computed(() => {
  if (props.contentRuled) {
    return '';
  }

  return props.variant === 'home' ? 'border-b border-border pb-3' : 'border-t border-border pt-3';
});

const statusRegistry = inject(OVERVIEW_STATUS_KEY, null);
const statusId = useId();

watchEffect(() => {
  if (!statusRegistry) {
    return;
  }

  if (!props.empty) {
    statusRegistry.remove(statusId);
    return;
  }

  statusRegistry.set(statusId, { title: props.title, emptyText: props.emptyText, icon: props.icon });
});

onBeforeUnmount(() => statusRegistry?.remove(statusId));
</script>
