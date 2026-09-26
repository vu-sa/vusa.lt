<template>
  <div :class="cn('relative flex w-full items-center', props.class)" data-slot="time-picker">
    <!-- Plain input: ui/input's own model would restore a key onInput rejected. pr-11 keeps the clear
         button's room even while empty, so choosing a time never narrows the field. -->
    <input
      v-bind="$attrs"
      :value="text"
      data-slot="input"
      :class="cn(inputVariants({ size }), clearable && 'pr-11')"
      :type="isCoarsePointer ? 'time' : 'text'"
      :inputmode="isCoarsePointer ? undefined : 'numeric'"
      :maxlength="isCoarsePointer ? undefined : 5"
      :placeholder
      :list="isCoarsePointer ? undefined : suggestionsId"
      :disabled
      @input="onInput"
      @blur="commit"
      @keydown.enter="commit"
    >
    <datalist v-if="!isCoarsePointer" :id="suggestionsId">
      <option v-for="time in suggestions" :key="time" :value="time" />
    </datalist>
    <Button
      v-if="clearable && selectedTime"
      type="button"
      variant="ghost"
      size="icon"
      class="absolute inset-y-0 right-0 h-full w-11 text-muted-foreground hover:bg-transparent hover:text-foreground"
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
import { inputVariants } from '@/Components/ui/input';
import { useCoarsePointer } from '@/Composables/useCoarsePointer';

interface TimeValue {
  hour: number;
  minute: number;
}

// id and aria-label belong on the input, so a form label and screen readers reach the field.
defineOptions({ inheritAttrs: false });

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
  /** Matches `ui/input` sizes; the clear button follows so the row stays one height. */
  size?: 'default' | 'sm';
  /** Starts the suggestion list here and runs it `suggestionHours` forward, instead of the whole day. */
  suggestFrom?: TimeValue;
  suggestionHours?: number;
}>(), {
  suggestFrom: undefined,
  suggestionHours: 6,
  minuteStep: 5,
  hourRange: () => [0, 23] as [number, number],
  disabled: false,
  placeholder: '--:--',
  size: 'default',
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
  const step = props.minuteStep;
  const from = props.suggestFrom
    ? Math.max(minHour * 60, Math.floor((props.suggestFrom.hour * 60 + props.suggestFrom.minute) / step) * step)
    : minHour * 60;
  const to = props.suggestFrom
    ? Math.min(maxHour * 60 + 59, from + props.suggestionHours * 60)
    : maxHour * 60 + 59;
  const result: string[] = [];

  for (let minutes = from; minutes <= to; minutes += step) {
    result.push(`${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`);
  }

  return result;
});

const format = (time: TimeValue | undefined): string => time
  ? `${String(time.hour).padStart(2, '0')}:${String(time.minute).padStart(2, '0')}`
  : '';

/** What the field shows while typing; it snaps back to a saved time on blur. */
const text = ref(format(selectedTime.value));

watch(selectedTime, (time) => {
  text.value = format(time);
});

/** Accepts `14:30`, `9:30`, `930`, `1430`, `9` and `14`. The step only shapes the suggestions. */
const parse = (value: string): TimeValue | undefined => {
  const match = value.match(/^(\d{1,2}):(\d{2})$/)
    ?? value.match(/^(\d{1,2})(\d{2})$/)
    ?? value.match(/^(\d{1,2})():?$/);
  if (!match) {
    return undefined;
  }

  const hour = Number(match[1]);
  const minute = Number(match[2] || 0);
  const [minHour, maxHour] = props.hourRange;

  return hour >= minHour && hour <= maxHour && minute <= 59 ? { hour, minute } : undefined;
};

const select = (time: TimeValue) => {
  if (format(time) !== format(selectedTime.value)) {
    selectedTime.value = time;
    updateAndEmit(time);
  }
  text.value = format(time);
};

const clear = () => {
  selectedTime.value = undefined;
  text.value = '';
  updateAndEmit(undefined);
};

function onInput(event: Event): void {
  const input = event.target as HTMLInputElement;
  // One colon, digits only; written back so a rejected key never shows.
  const [hours = '', ...rest] = input.value.replace(/[^\d:]/g, '').split(':');
  const sanitized = (rest.length ? `${hours}:${rest.join('')}` : hours).slice(0, 5);
  input.value = sanitized;
  text.value = sanitized;

  // A full time (typed or picked from the suggestions) is saved at once; shorter forms wait for blur.
  if (/^\d{2}:\d{2}$/.test(sanitized)) {
    const time = parse(sanitized);
    if (time) {
      select(time);
    }
  }
}

function commit(): void {
  if (text.value === '') {
    if (props.clearable || isCoarsePointer.value) {
      if (selectedTime.value) {
        clear();
      }
    }
    else {
      text.value = format(selectedTime.value);
    }
    return;
  }

  const time = parse(text.value);
  if (time) {
    select(time);
  }
  else {
    text.value = format(selectedTime.value);
  }
}
</script>
