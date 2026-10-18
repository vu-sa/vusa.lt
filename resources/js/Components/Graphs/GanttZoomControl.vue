<template>
  <div class="flex items-center gap-1" data-slot="gantt-zoom">
    <Button
      type="button"
      :size="bordered ? 'icon-sm' : 'icon-xs'"
      :variant="bordered ? 'outline' : 'ghost'"
      class="pointer-coarse:size-11"
      :disabled="position <= range.min"
      :aria-label="$t('Atitolinti')"
      @click="stepBy(-range.step)"
    >
      <ZoomOut class="size-3.5" />
    </Button>
    <Slider
      :model-value="[position]"
      :min="range.min"
      :max="range.max"
      :step="range.step"
      class="w-24 max-sm:hidden"
      :aria-label="$t('Mastelis')"
      @update:model-value="value => value?.[0] !== undefined && emit('update:modelValue', toValue(value[0]))"
    />
    <Button
      type="button"
      :size="bordered ? 'icon-sm' : 'icon-xs'"
      :variant="bordered ? 'outline' : 'ghost'"
      class="pointer-coarse:size-11"
      :disabled="position >= range.max"
      :aria-label="$t('Priartinti')"
      @click="stepBy(range.step)"
    >
      <ZoomIn class="size-3.5" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ZoomIn, ZoomOut } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Slider } from '@/Components/ui/slider';

/**
 * The zoom both timelines share; the unit (day or month width) is the caller's. With `stops`, the
 * slider and buttons move through those values instead of an even min/max/step scale.
 */
const props = defineProps<{
  modelValue: number;
  min?: number;
  max?: number;
  step?: number;
  stops?: readonly number[];
  /** Outlined steps, for toolbars whose other controls are bordered too. */
  bordered?: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: number];
}>();

const range = computed(() => (props.stops?.length
  ? { min: 0, max: props.stops.length - 1, step: 1 }
  : { min: props.min ?? 0, max: props.max ?? 0, step: props.step ?? 1 }));

/** The slider position: an index into `stops`, or the value itself. */
const position = computed(() => {
  if (!props.stops?.length) return props.modelValue;

  let nearest = 0;
  props.stops.forEach((stop, index) => {
    if (Math.abs(stop - props.modelValue) < Math.abs(props.stops![nearest]! - props.modelValue)) nearest = index;
  });
  return nearest;
});

function toValue(position: number): number {
  return props.stops?.length ? props.stops[position]! : position;
}

function stepBy(delta: number): void {
  emit('update:modelValue', toValue(Math.min(range.value.max, Math.max(range.value.min, position.value + delta))));
}
</script>
