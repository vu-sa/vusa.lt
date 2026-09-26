<template>
  <div class="flex shrink-0 items-center justify-end gap-2" data-slot="collection-row-actions">
    <template v-for="action in actions" :key="action.key">
      <component
        :is="action.href && !action.external ? Link : action.href ? 'a' : 'button'"
        :href="action.href"
        :type="action.href ? undefined : 'button'"
        :target="action.external ? '_blank' : undefined"
        :rel="action.external ? 'noopener noreferrer' : undefined"
        :aria-label="action.labelled ? undefined : action.label"
        :title="action.labelled ? undefined : action.label"
        :class="[
          action.labelled ? labelledClass : iconClass,
          action.destructive ? 'hover:border-destructive hover:text-destructive' : 'hover:border-brand hover:text-brand',
        ]"
        @click="action.href ? undefined : emit('select', action.key)"
      >
        <component :is="action.icon" v-if="action.icon" class="size-4 shrink-0" aria-hidden="true" />
        <span v-if="action.labelled">{{ action.label }}</span>
      </component>
    </template>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

export interface CollectionRowAction {
  key: string;
  label: string;
  icon?: Component;
  /** Resolved URL; without it the action emits `select` with its key. */
  href?: string;
  external?: boolean;
  /** Shows the label beside the icon ("Redaguoti"). One per row at most — the rest are icons. */
  labelled?: boolean;
  destructive?: boolean;
}

defineProps<{
  actions: CollectionRowAction[];
}>();

const emit = defineEmits<{
  select: [key: string];
}>();

// Always visible, never hover-revealed: nothing may be hover-only (.ai/rules/js-pages-admin.md).
const iconClass = [
  'flex size-9 items-center justify-center border border-border text-muted-foreground transition-colors',
  'pointer-coarse:size-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
].join(' ');

const labelledClass = [
  'inline-flex min-h-9 items-center gap-1.5 border border-border px-3 text-xs font-bold normal-case tracking-normal text-foreground transition-colors',
  'pointer-coarse:min-h-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
].join(' ');
</script>
