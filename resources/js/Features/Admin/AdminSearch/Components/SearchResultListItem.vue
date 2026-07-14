<template>
  <!-- Selectable mode: a checkbox/radio indicator alongside the hit row.
       The row body highlights (drives the detail pane); the indicator toggles membership. -->
  <div
    v-if="selectable"
    role="button"
    tabindex="0"
    :aria-disabled="disabled"
    :class="[
      'group flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors',
      disabled && 'opacity-50',
      selected ? 'bg-accent ring-1 ring-primary/20' : 'hover:bg-accent/60',
    ]"
    @click="onRowClick"
    @keydown.enter.prevent="!disabled && $emit('toggle')"
    @keydown.space.prevent="!disabled && $emit('toggle')"
  >
    <div class="shrink-0" @click.stop>
      <Checkbox
        v-if="multiple"
        :model-value="checked"
        :disabled
        @update:model-value="!disabled && $emit('toggle')"
      />
      <button
        v-else
        type="button"
        :disabled
        class="flex size-4 items-center justify-center rounded-full border text-primary"
        :class="checked ? 'border-primary' : 'border-muted-foreground/40'"
        @click="!disabled && $emit('toggle')"
      >
        <span v-if="checked" class="size-2 rounded-full bg-primary" />
      </button>
    </div>
    <SearchHitRow :hit :selected />
  </div>

  <!-- Default (navigation) mode — unchanged. -->
  <button
    v-else
    type="button"
    :class="[
      'group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors',
      selected ? 'bg-accent ring-1 ring-primary/20' : 'hover:bg-accent/60',
    ]"
    @click="$emit('select')"
  >
    <SearchHitRow :hit :selected />
  </button>
</template>

<script setup lang="ts">
import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import SearchHitRow from './SearchHitRow.vue';

import { Checkbox } from '@/Components/ui/checkbox';

const props = defineProps<{
  hit: NormalizedSearchHit;
  selected?: boolean;
  /** Enables the checkbox/radio selection affordance. */
  selectable?: boolean;
  /** Multi-select renders a checkbox; single-select renders a radio dot. */
  multiple?: boolean;
  /** Whether this hit is currently part of the selection. */
  checked?: boolean;
  /** Greys out and blocks toggling (e.g. a resource with no availability). */
  disabled?: boolean;
}>();

const emit = defineEmits<{
  select: [];
  toggle: [];
}>();

/**
 * Clicking a row always highlights it for the detail pane (even when disabled,
 * so unselectable rows can still be inspected). In single-select mode the row
 * also toggles the selection, so a click both picks and previews the option.
 */
function onRowClick() {
  emit('select');
  if (!props.multiple && !props.disabled) {
    emit('toggle');
  }
}
</script>
