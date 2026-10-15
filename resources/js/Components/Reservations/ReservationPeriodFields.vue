<template>
  <div class="space-y-3" data-slot="reservation-period-fields">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div class="space-y-1.5" role="group" :aria-labelledby="`${idPrefix}-start-label`">
        <Label :id="`${idPrefix}-start-label`" class="text-sm font-medium">{{ $t('reservations.cart.start') }}{{ required ? ' *' : '' }}</Label>
        <DateTimePicker
          :model-value="start"
          variant="popover"
          :minute-step="15"
          :placeholder="$t('reservations.cart.pick_start')"
          @update:model-value="onStart"
        />
        <p v-if="startError" class="text-xs text-destructive">
          {{ startError }}
        </p>
      </div>

      <div class="space-y-1.5" role="group" :aria-labelledby="`${idPrefix}-end-label`">
        <Label :id="`${idPrefix}-end-label`" class="text-sm font-medium">{{ $t('reservations.cart.end') }}{{ required ? ' *' : '' }}</Label>
        <DateTimePicker
          :model-value="end"
          variant="popover"
          :minute-step="15"
          :placeholder="$t('reservations.cart.pick_end')"
          @update:model-value="onEnd"
        />
        <p v-if="endError || endBeforeStart" class="text-xs text-destructive">
          {{ endError ?? $t('reservations.cart.end_before_start') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';
import { Label } from '@/Components/ui/label';

export interface ReservationPeriod {
  start: number;
  end: number;
}

const props = withDefaults(defineProps<{
  modelValue: ReservationPeriod | null;
  idPrefix?: string;
  required?: boolean;
  startError?: string;
  endError?: string;
}>(), {
  idPrefix: 'reservation-period',
  startError: undefined,
  endError: undefined,
});

const emit = defineEmits<{
  'update:modelValue': [value: ReservationPeriod];
}>();

const toDate = (timestamp: number | undefined) => (timestamp ? new Date(timestamp) : null);

const start = ref<Date | null>(toDate(props.modelValue?.start));
const end = ref<Date | null>(toDate(props.modelValue?.end));

const endBeforeStart = computed(() => !!start.value && !!end.value && end.value <= start.value);

// Only a complete, forward period is saved; a half-picked one stays local until it is.
const commit = () => {
  if (!start.value || !end.value || endBeforeStart.value) {
    return;
  }

  const next = { start: start.value.getTime(), end: end.value.getTime() };

  if (next.start !== props.modelValue?.start || next.end !== props.modelValue?.end) {
    emit('update:modelValue', next);
  }
};

const onStart = (value: Date | null) => {
  start.value = value;
  commit();
};

const onEnd = (value: Date | null) => {
  end.value = value;
  commit();
};

// The server answers every change with a fresh cart; only resync when it disagrees.
watch(() => props.modelValue, (period) => {
  if (period?.start !== start.value?.getTime() || period?.end !== end.value?.getTime()) {
    start.value = toDate(period?.start);
    end.value = toDate(period?.end);
  }
});
</script>
