<template>
  <div v-if="actions.length" class="flex shrink-0 items-center justify-end gap-2" data-slot="collection-row-actions">
    <template v-for="action in visibleActions" :key="action.key">
      <component
        :is="action.href && !action.external && !action.plain ? Link : action.href ? 'a' : 'button'"
        :data-action="action.key"
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
          action.destructive ? 'hover:border-destructive hover:text-destructive' : 'hover:border-brand hover:text-brand',
        ]"
        @click="event => activate(action, event)"
      >
        <component :is="action.icon" v-if="action.icon" :class="['size-4 shrink-0', action.loading && 'animate-spin']" aria-hidden="true" />
        <span v-if="action === labelledAction">{{ fullLabels ? action.label : (action.shortLabel ?? action.label) }}</span>
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

const props = defineProps<{ actions: CollectionRowAction[]; fullLabels?: boolean; spotlight?: boolean }>();
const labelledAction = computed(() =>
  props.actions.find(action => action.primary)
  ?? props.actions.find(action => action.role === 'edit')
  ?? props.actions.find(action => action.role === 'open'),
);
const visibleActions = computed(() => {
  const icons = ['open', 'edit'].flatMap(role =>
    props.actions.filter(action => action !== labelledAction.value && action.role === role),
  );

  return [labelledAction.value, ...icons]
    .filter((action): action is CollectionRowAction => Boolean(action));
});
const overflowActions = computed(() => props.actions.filter(action => !visibleActions.value.includes(action)));

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

const iconClass = [
  'flex size-9 items-center justify-center border border-border text-muted-foreground transition-colors',
  'pointer-coarse:size-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50',
].join(' ');

const labelledClass = [
  'inline-flex min-h-9 items-center gap-1.5 border border-border px-3 text-xs font-bold normal-case tracking-normal text-foreground transition-colors',
  'pointer-coarse:min-h-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50',
].join(' ');
</script>
