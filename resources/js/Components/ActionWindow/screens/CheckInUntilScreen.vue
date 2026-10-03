<template>
  <ActionWindowScreen
    :title="$t('action_window.check_in.until.title')"
    :subtitle="$t('action_window.check_in.until.subtitle')"
  >
    <!-- Explanation, not a warning: nothing is wrong, so it takes no status colour. -->
    <div class="mb-4 flex gap-3 border border-border bg-secondary p-3">
      <Info class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
      <div class="text-sm">
        <p class="font-medium text-foreground">
          {{ $t('action_window.check_in.explainer_title') }}
        </p>
        <p class="mt-1 text-muted-foreground">
          {{ $t('action_window.check_in.explainer') }}
        </p>
      </div>
    </div>

    <ActionChoiceList v-if="!custom">
      <ActionChoiceButton
        v-for="preset in presets"
        :key="preset.key"
        :title="preset.label"
        :description="preset.detail"
        :icon="CalendarOff"
        :show-chevron="false"
        @click="pick(preset.date)"
      />
      <ActionChoiceButton
        :title="$t('action_window.check_in.until.custom')"
        :description="$t('action_window.check_in.until.max_hint')"
        :icon="CalendarSearch"
        @click="custom = true"
      />
    </ActionChoiceList>

    <div v-else class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-2">
          <Label>{{ $t('action_window.check_in.until.from') }}</Label>
          <DatePicker v-model="startDate" class="w-full" />
        </div>
        <div class="space-y-2">
          <Label>{{ $t('action_window.check_in.until.to') }}</Label>
          <DatePicker v-model="endDate" class="w-full" />
        </div>
      </div>
      <p class="text-xs text-muted-foreground">
        {{ $t('action_window.check_in.until.max_hint') }}
      </p>
      <Button variant="ghost" size="sm" class="pointer-coarse:h-11" @click="custom = false">
        <ChevronLeft class="size-4" />
        {{ $t('action_window.common.back') }}
      </Button>
    </div>

    <template v-if="custom" #footer>
      <ActionWindowPrimaryButton :disabled="!isValidRange" @click="pick(endDate)">
        {{ $t('action_window.common.continue') }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarOff, CalendarSearch, ChevronLeft, Info } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { toPickerDate, useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';

/** StoreInstitutionCheckInRequest refuses an end date beyond this. */
const MAX_MONTHS_AHEAD = 3;

const { advance, updateCheckIn } = useActionWindow();

const custom = ref(false);
const startDate = ref<Date>(toPickerDate(new Date()));
const endDate = ref<Date>(toPickerDate(daysFromNow(14)));

function daysFromNow(days: number): Date {
  const date = new Date();
  date.setDate(date.getDate() + days);
  return date;
}

const maxDate = computed(() => {
  const date = new Date();
  date.setMonth(date.getMonth() + MAX_MONTHS_AHEAD);
  return date;
});

const endOfMonth = computed(() => {
  const date = new Date();
  return new Date(date.getFullYear(), date.getMonth() + 1, 0);
});

const dates = useWindowDates();

const presets = computed(() => [
  { key: 'two_weeks', label: $t('action_window.check_in.until.two_weeks'), date: daysFromNow(14), detail: dates.day(daysFromNow(14)) },
  { key: 'month_end', label: $t('action_window.check_in.until.month_end'), date: endOfMonth.value, detail: dates.day(endOfMonth.value) },
  { key: 'three_months', label: $t('action_window.check_in.until.three_months'), date: maxDate.value, detail: dates.day(maxDate.value) },
]);

const isValidRange = computed(() => endDate.value > startDate.value && endDate.value <= maxDate.value);

/** The server takes plain dates; sending an ISO datetime would fail `date_format`. */
const toIsoDate = (date: Date): string =>
  `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const pick = (end: Date) => {
  // Presets always start today; the custom range may start earlier.
  const start = custom.value ? startDate.value : new Date();
  const clamped = end > maxDate.value ? maxDate.value : end;

  updateCheckIn({ startDate: toIsoDate(start), endDate: toIsoDate(clamped) });
  advance('checkin.review');
};
</script>
