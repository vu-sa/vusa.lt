<template>
  <div class="flex items-center gap-1" data-slot="gantt-zoom">
    <Button
      type="button"
      size="icon-xs"
      variant="ghost"
      class="pointer-coarse:size-11"
      :disabled="modelValue <= min"
      :aria-label="$t('Atitolinti')"
      @click="stepBy(-step)"
    >
      <ZoomOut class="size-3.5" />
    </Button>
    <Slider
      :model-value="[modelValue]"
      :min
      :max
      :step
      class="w-24"
      :aria-label="$t('Mastelis')"
      @update:model-value="value => value?.[0] !== undefined && emit('update:modelValue', value[0])"
    />
    <Button
      type="button"
      size="icon-xs"
      variant="ghost"
      class="pointer-coarse:size-11"
      :disabled="modelValue >= max"
      :aria-label="$t('Priartinti')"
      @click="stepBy(step)"
    >
      <ZoomIn class="size-3.5" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ZoomIn, ZoomOut } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Slider } from '@/Components/ui/slider';

/** The zoom both timelines share; the unit (day or month width) is the caller's. */
const props = defineProps<{
  modelValue: number;
  min: number;
  max: number;
  step: number;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: number];
}>();

function stepBy(delta: number): void {
  emit('update:modelValue', Math.min(props.max, Math.max(props.min, props.modelValue + delta)));
}
</script>
