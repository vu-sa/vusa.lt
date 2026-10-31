<template>
  <div v-if="actions.length" :class="['flex shrink-0 items-center gap-2', emphasis ? 'justify-start' : 'justify-end']" data-slot="collection-row-actions">
    <template v-for="action in visibleActions" :key="action.key">
      <component
        :is="action.href && !action.external && !action.plain ? Link : action.href ? 'a' : 'button'"
        :data-action="action.key"
        :data-collection-row-action="action === labelledAction ? 'primary' : undefined"
        :href="action.href"
        :type="action.href ? undefined : 'button'"
        :target="action.external ? '_blank' : undefined"
        :rel="action.external ? 'noopener noreferrer' : undefined"
        :disabled="!action.href && (action.disabled || action.loading) ? true : undefined"
        :aria-busy="action.loading || undefined"
        :aria-disabled="action.disabled || action.loading || undefined"
        :tabindex="action.href && (action.disabled || action.loading) ? -1 : undefined"
        :aria-label="action === labelledAction ? undefined : action.label"
        :title="action === labelledAction ? undefined : action.label"
        :class="[
          action === labelledAction ? labelledClass : iconClass,
          (action.disabled || action.loading) && 'pointer-events-none opacity-50',
          actionHoverClass(action),
          stacked && 'max-w-64',
        ]"
        @click="event => activate(action, event)"
      >
        <component :is="action.icon" v-if="action.icon" :class="['size-4 shrink-0', action.loading && 'animate-spin']" aria-hidden="true" />
        <span v-if="action === labelledAction" class="min-w-0 truncate">{{ fullLabels || stacked ? action.label : (action.shortLabel ?? action.label) }}</span>
      </component>
    </template>
    <RecordActionMenu :actions="overflowActions" :spotlight trigger-size="compact" @select="emit('select', $event)" @navigate="emit('navigate', $event)" />
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import RecordActionMenu from '@/Components/Patterns/RecordActionMenu.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

export type CollectionRowAction = AdminRecordAction;

const props = defineProps<{
  actions: CollectionRowAction[];
  fullLabels?: boolean;
  spotlight?: boolean;
  /** Fills the primary action, for a pane where it is the region's one call to action. */
  emphasis?: boolean;
  /** For `CollectionRow`: its title already opens the record, so no separate open action. */
  stacked?: boolean;
  /** Moves the primary action to the top of the menu, for a row too narrow to show it. */
  foldPrimary?: boolean;
}>();
const availableActions = computed(() => (props.stacked ? props.actions.filter(action => action.role !== 'open') : props.actions));
const labelledAction = computed(() =>
  availableActions.value.find(action => action.primary)
  ?? (props.stacked
    ? undefined
    : availableActions.value.find(action => action.role === 'edit') ?? availableActions.value.find(action => action.role === 'open')),
);
const visibleActions = computed(() => {
  if (props.stacked) {
    return labelledAction.value && !props.foldPrimary ? [labelledAction.value] : [];
  }

  const icons = ['open', 'edit'].flatMap(role =>
    props.actions.filter(action => action !== labelledAction.value && action.role === role),
  );

  return [labelledAction.value, ...icons]
    .filter((action): action is CollectionRowAction => Boolean(action));
});
const overflowActions = computed(() => availableActions.value.filter(action => !visibleActions.value.includes(action)));

function activate(action: AdminRecordAction, event: MouseEvent): void {
  if (action.disabled || action.loading) {
    event.preventDefault();
    return;
  }
  if (action.href) {
    emit('navigate', action.key);
  }
  else {
    emit('select', action.key);
  }
}

const emit = defineEmits<{
  select: [key: string];
  navigate: [key: string];
}>();

function actionHoverClass(action: CollectionRowAction): string {
  const labelled = action === labelledAction.value;

  if (props.emphasis && labelled) {
    return 'border-brand-fill bg-brand-fill text-brand-foreground hover:opacity-90';
  }

  return [
    labelled && 'border-border text-foreground',
    action.destructive ? 'hover:border-destructive hover:text-destructive' : 'hover:border-brand hover:text-brand',
  ].filter(Boolean).join(' ');
}

const iconClass = [
  'flex size-9 items-center justify-center border border-border text-muted-foreground transition-colors',
  'pointer-coarse:size-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50',
].join(' ');

const labelledClass = [
  'inline-flex min-h-9 items-center gap-1.5 border px-3 text-xs font-bold normal-case tracking-normal transition-colors',
  'pointer-coarse:min-h-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50',
].join(' ');
</script>
