<template>
  <div :class="cn('flex w-full items-center gap-2', props.class)">
    <Input
      :model-value="formattedTime"
      :type="isCoarsePointer ? 'time' : 'text'"
      :inputmode="isCoarsePointer ? undefined : 'numeric'"
      :placeholder
      :list="isCoarsePointer ? undefined : suggestionsId"
      :disabled
      @update:model-value="updateFromInput"
    />
    <datalist v-if="!isCoarsePointer" :id="suggestionsId">
      <option v-for="time in suggestions" :key="time" :value="time" />
    </datalist>
    <Button
      v-if="clearable && selectedTime"
      type="button"
      variant="ghost"
      size="icon"
      :disabled
      :aria-label="$t('Išvalyti')"
      @click="clear"
    >
      <X class="size-4" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { computed, ref, useId, watch, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useCoarsePointer } from '@/Composables/useCoarsePointer';

interface TimeValue {
  hour: number;
  minute: number;
}

const props = withDefaults(defineProps<{
  modelValue?: TimeValue;
  class?: HTMLAttributes['class'];
  minuteStep?: number;
  hourRange?: [number, number]; // [min, max]
  disabled?: boolean;
  /** Shown when no time is set. */
  placeholder?: string;
  /** Adds an inline clear affordance that emits `undefined`. */
  clearable?: boolean;
}>(), {
  minuteStep: 5,
  hourRange: () => [0, 23] as [number, number],
  disabled: false,
  placeholder: '--:--',
  clearable: false,
});

const emit = defineEmits<(e: 'update:modelValue', value: TimeValue | undefined) => void>();

const isCoarsePointer = useCoarsePointer();
const suggestionsId = `time-suggestions-${useId()}`;

// Undefined means "no time set" — the trigger then shows the placeholder rather than
// pretending 12:00 was chosen.
const selectedTime = ref<TimeValue | undefined>(props.modelValue);

// Use a flag to prevent recursive updates
const isInternalUpdate = ref(false);

// Watch for props modelValue changes
watch(() => props.modelValue, (newValue) => {
  // Only update if this wasn't triggered by our own emit
  if (!isInternalUpdate.value) {
    selectedTime.value = newValue ? { ...newValue } : undefined;
  }
}, { deep: true });

// Watch for internal selected time changes - only emit when direct user changes occur
const updateAndEmit = (newTime: TimeValue | undefined) => {
  isInternalUpdate.value = true;
  emit('update:modelValue', newTime ? { ...newTime } : undefined);
  // Reset flag after the current call stack completes
  setTimeout(() => {
    isInternalUpdate.value = false;
  }, 0);
};

const suggestions = computed(() => {
  const [minHour, maxHour] = props.hourRange;
  const result: string[] = [];

  for (let hour = minHour; hour <= maxHour; hour++) {
    for (let minute = 0; minute < 60; minute += props.minuteStep) {
      result.push(`${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`);
    }
  }

  return result;
});

// Format the time for display
const formattedTime = computed(() => {
  if (!selectedTime.value) return '';

  return `${selectedTime.value.hour.toString().padStart(2, '0')}:${selectedTime.value.minute.toString().padStart(2, '0')}`;
});

const clear = () => {
  selectedTime.value = undefined;
  updateAndEmit(undefined);
};

function updateFromInput(value: string | number): void {
  const match = String(value).match(/^(\d{2}):(\d{2})$/);
  if (!match) {
    return;
  }

  const hour = Number(match[1]);
  const minute = Number(match[2]);
  const [minHour, maxHour] = props.hourRange;

  if (hour < minHour || hour > maxHour || minute > 59 || minute % props.minuteStep !== 0) {
    return;
  }

  const time = { hour, minute };
  selectedTime.value = time;
  updateAndEmit(time);
}
</script>
