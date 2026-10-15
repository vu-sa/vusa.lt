<template>
  <ActionWindowScreen :title="$t('action_window.check_in.until.title')" :subtitle>
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

    <div v-if="isLoading" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full" />
    </div>

    <template v-else-if="!custom">
      <ActionChoiceList>
        <ActionChoiceButton
          v-for="preset in presets"
          :key="preset.key"
          :title="preset.label"
          :description="preset.detail"
          :icon="CalendarOff"
          :show-chevron="false"
          @click="pick(from, preset.date)"
        />
        <ActionChoiceButton
          :title="$t('action_window.check_in.until.custom')"
          :description="$t('action_window.check_in.until.max_hint')"
          :icon="CalendarSearch"
          @click="custom = true"
        />
      </ActionChoiceList>

      <p v-if="nextMeetingCap" class="mt-3 text-xs text-muted-foreground">
        {{ $t('action_window.check_in.until.next_meeting_cap', { date: dates.day(nextMeetingCap.meeting) }) }}
      </p>
    </template>

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
      <ActionWindowPrimaryButton :disabled="!isValidRange" @click="pick(startDate, endDate)">
        {{ $t('action_window.common.continue') }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarOff, CalendarSearch, ChevronLeft, Info } from '@lucide/vue';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { toPickerDate, useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData } from '@/Composables/useActionWindowData';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';
import { Skeleton } from '@/Components/ui/skeleton';

/** StoreInstitutionCheckInRequest refuses an end date beyond this. */
const MAX_MONTHS_AHEAD = 3;

const { draft, advance, updateCheckIn } = useActionWindow();
const { institutions, isLoading, load } = useActionWindowData();
const dates = useWindowDates();

onMounted(load);

const startOfDay = (date: Date): Date => new Date(date.getFullYear(), date.getMonth(), date.getDate());

const addDays = (date: Date, days: number): Date => {
  const shifted = new Date(date);
  shifted.setDate(shifted.getDate() + days);
  return shifted;
};

/** "2026-09-01" as a local date; `new Date()` would read it as UTC midnight. */
const fromIsoDate = (value: string): Date => {
  const [year, month, day] = value.split('-').map(Number);
  return new Date(year ?? 1970, (month ?? 1) - 1, day ?? 1);
};

const today = startOfDay(new Date());

const maxDate = (() => {
  const date = new Date(today);
  date.setMonth(date.getMonth() + MAX_MONTHS_AHEAD);
  return date;
})();

const institution = computed(() => institutions.value.find(item => item.id === draft.institution?.id));

// The server suggests where the silence began, so the rep confirms the past instead of only promising the future.
const from = computed(() => institution.value?.check_in_from ? fromIsoDate(institution.value.check_in_from) : today);
const reason = computed(() => institution.value?.check_in_from ? institution.value.check_in_from_reason ?? 'today' : 'today');

const subtitle = computed(() => isLoading.value
  ? undefined
  : $t('action_window.check_in.until.subtitle', {
      date: dates.day(from.value),
      reason: $t(`action_window.check_in.until.reasons.${reason.value}`),
    }));

/** The server rejects a period containing a meeting, so an upcoming one ends it the day before. */
const nextMeetingCap = computed(() => {
  const next = institution.value?.activity_status.next_meeting_at;
  if (!next) {
    return null;
  }
  const meeting = startOfDay(new Date(next));
  return { meeting, end: addDays(meeting, -1) };
});

const presets = computed(() => {
  const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  const candidates = [
    { key: 'until_today', label: $t('action_window.check_in.until.until_today'), date: today },
    { key: 'two_weeks', label: $t('action_window.check_in.until.two_weeks'), date: addDays(today, 14) },
    { key: 'month_end', label: $t('action_window.check_in.until.month_end'), date: endOfMonth },
  ];
  const seen = new Set<number>();

  return candidates
    .map(candidate => ({
      ...candidate,
      date: [candidate.date, nextMeetingCap.value?.end ?? maxDate, maxDate].reduce((a, b) => (a < b ? a : b)),
    }))
    .filter((candidate) => {
      if (candidate.date <= from.value || seen.has(candidate.date.getTime())) {
        return false;
      }
      seen.add(candidate.date.getTime());
      return true;
    })
    .map((candidate) => {
      // The whole range, so the rep sees the start the server chose as well as the end they pick.
      const range = `${dates.day(from.value)} – ${dates.day(candidate.date)}`;

      return {
        ...candidate,
        detail: candidate.key === 'until_today'
          ? $t('action_window.check_in.until.until_today_detail', { range })
          : range,
      };
    });
});

const custom = ref(false);
const startDate = ref<Date>(toPickerDate(today));
const endDate = ref<Date>(toPickerDate(addDays(today, 14)));

watch(from, (value) => {
  startDate.value = toPickerDate(value);
}, { immediate: true });

const isValidRange = computed(() => endDate.value > startDate.value && endDate.value <= maxDate);

/** The server takes plain dates; sending an ISO datetime would fail `date_format`. */
const toIsoDate = (date: Date): string =>
  `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const pick = (start: Date, end: Date) => {
  updateCheckIn({ startDate: toIsoDate(start), endDate: toIsoDate(end > maxDate ? maxDate : end) });
  advance('checkin.review');
};
</script>
