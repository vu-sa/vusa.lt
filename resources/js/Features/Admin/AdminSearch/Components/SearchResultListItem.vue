<template>
  <!-- Selectable mode: a checkbox/radio indicator alongside the hit row.
       The row body highlights (drives the detail pane); the indicator toggles membership. -->
  <div
    v-if="selectable"
    role="button"
    tabindex="0"
    :aria-disabled="disabled"
    :class="[
      'group flex min-h-11 w-full cursor-pointer items-center gap-3 px-3 py-2.5 text-left transition-colors focus-visible:ring-2 focus-visible:ring-ring',
      disabled && 'opacity-50',
      selected ? 'bg-secondary' : 'hover:bg-secondary/40',
    ]"
    @click="onRowClick"
    @keydown.enter.prevent="!disabled && $emit('toggle')"
    @keydown.space.prevent="!disabled && $emit('toggle')"
  >
    <!-- One square mark for both modes (admin has no round controls); the 44px target is the button. -->
    <button
      type="button"
      :role="multiple ? 'checkbox' : 'radio'"
      :aria-checked="checked"
      :aria-label="hit.title"
      :disabled
      class="-ml-1.5 flex size-8 shrink-0 items-center justify-center focus-visible:ring-2 focus-visible:ring-ring pointer-coarse:size-11"
      @click.stop="!disabled && $emit('toggle')"
    >
      <span
        aria-hidden="true"
        :class="[
          'flex size-4.5 items-center justify-center border transition-colors',
          checked ? 'border-brand-fill' : 'border-input bg-card group-hover:border-brand/80',
          checked && multiple && 'bg-brand-fill text-brand-foreground',
        ]"
      >
        <Check v-if="checked && multiple" class="size-3.5" />
        <span v-else-if="checked" class="size-2.5 bg-brand-fill" />
      </span>
    </button>
    <SearchHitRow :hit :selected />
  </div>

  <!-- Navigation mode -->
  <button
    v-else
    type="button"
    :class="[
      'group flex min-h-11 w-full items-center gap-3 px-3 py-2.5 text-left transition-colors focus-visible:ring-2 focus-visible:ring-ring',
      selected ? 'bg-secondary' : 'hover:bg-secondary/40',
    ]"
    @click="$emit('select')"
  >
    <SearchHitRow :hit :selected />
  </button>
</template>

<script setup lang="ts">
import { Check } from '@lucide/vue';

import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import SearchHitRow from './SearchHitRow.vue';

const props = defineProps<{
  hit: NormalizedSearchHit;
  selected?: boolean;
  /** Enables the checkbox/radio selection affordance. */
  selectable?: boolean;
  /** Multi-select marks with a check; single-select with a filled square. */
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
