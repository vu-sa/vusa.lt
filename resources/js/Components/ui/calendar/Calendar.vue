<template>
  <CalendarRoot
    v-slot="{ grid, weekDays }"
    data-slot="calendar"
    :class="cn('p-3', props.class)"
    :placeholder="currentPlaceholder"
    v-bind="forwarded"
    @update:placeholder="handlePlaceholderChange"
  >
    <CalendarHeader class="w-full justify-between gap-2 pb-2 border-b border-border/60">
      <CalendarHeading class="min-w-0 flex-1">
        <div class="flex items-center gap-1.5">
          <div class="relative flex h-8 items-center border border-border bg-background pr-5 transition-colors hover:border-foreground/30 focus-within:border-brand">
            <select
              :value="String(currentPlaceholder.month)"
              aria-label="Mėnuo"
              class="h-full appearance-none bg-transparent pl-2 pr-0 text-xs font-bold uppercase tracking-wider text-foreground outline-none cursor-pointer"
              @change="handleMonthSelect(($event.target as HTMLSelectElement).value)"
            >
              <option v-for="(month, index) in monthNames" :key="index" :value="String(index + 1)">
                {{ month }}
              </option>
            </select>
            <ChevronDown class="pointer-events-none absolute right-1 size-3 text-muted-foreground" aria-hidden="true" />
          </div>

          <div class="relative flex h-8 items-center border border-border bg-background pr-5 transition-colors hover:border-foreground/30 focus-within:border-brand">
            <select
              :value="String(currentPlaceholder.year)"
              aria-label="Metai"
              class="h-full appearance-none bg-transparent pl-2 pr-0 text-xs font-bold text-foreground outline-none cursor-pointer"
              @change="handleYearSelect(($event.target as HTMLSelectElement).value)"
            >
              <option v-for="year in years" :key="year" :value="String(year)">
                {{ year }}
              </option>
            </select>
            <ChevronDown class="pointer-events-none absolute right-1 size-3 text-muted-foreground" aria-hidden="true" />
          </div>
        </div>
      </CalendarHeading>

      <div class="flex items-center gap-1">
        <CalendarPrevButton />
        <CalendarNextButton />
      </div>
    </CalendarHeader>

    <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
      <CalendarGrid v-for="month in grid" :key="month.value.toString()">
        <CalendarGridHead>
          <CalendarGridRow>
            <CalendarHeadCell
              v-for="day in weekDays" :key="day"
            >
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>
        <CalendarGridBody>
          <CalendarGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="mt-2 w-full">
            <CalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
            >
              <CalendarCellTrigger
                :day="weekDate"
                :month="month.value"
              />
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </div>
  </CalendarRoot>
</template>

<script lang="ts" setup>
import { computed, ref, type HTMLAttributes } from 'vue';
import { CalendarRoot, type CalendarRootEmits, type CalendarRootProps, useForwardPropsEmits } from 'reka-ui';
import { CalendarDate, getLocalTimeZone, today } from '@internationalized/date';
import { ChevronDown } from 'lucide-vue-next';

import { CalendarCell, CalendarCellTrigger, CalendarGrid, CalendarGridBody, CalendarGridHead, CalendarGridRow, CalendarHeadCell, CalendarHeader, CalendarHeading, CalendarNextButton, CalendarPrevButton } from '.';

import { cn } from '@/Utils/Shadcn/utils';

interface CalendarPropsExtended extends CalendarRootProps {
  class?: HTMLAttributes['class'];
  yearRange?: [number, number];
}

const props = withDefaults(defineProps<CalendarPropsExtended>(), {
  class: undefined,
  yearRange: () => [1989, new Date().getFullYear() + 1],
});
const emits = defineEmits<CalendarRootEmits>();

const delegatedProps = computed(() => {
  const { class: _, yearRange: __, ...delegated } = props;
  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);

// Abbreviated month names for the dropdown (prevent overflow)
const monthNames = [
  'Sau', 'Vas', 'Kov', 'Bal', 'Geg', 'Bir',
  'Lie', 'Rgp', 'Rgs', 'Spa', 'Lap', 'Grd',
];

// Generate years array from range
const years = computed(() => {
  const [start, end] = props.yearRange;
  return Array.from({ length: end - start + 1 }, (_, i) => start + i).reverse();
});

// Initialize with today's date or from model value
const getInitialPlaceholder = (): CalendarDate => {
  // Check if there's a modelValue or placeholder prop
  if (props.modelValue) {
    const mv = props.modelValue as Record<string, unknown>;
    if (typeof mv?.year === 'number' && typeof mv?.month === 'number') {
      return new CalendarDate(mv.year, mv.month, (mv.day as number) || 1);
    }
  }
  if (props.placeholder) {
    const ph = props.placeholder as Record<string, unknown>;
    if (typeof ph?.year === 'number' && typeof ph?.month === 'number') {
      return new CalendarDate(ph.year, ph.month, (ph.day as number) || 1);
    }
  }
  // Default to today
  return today(getLocalTimeZone());
};

// Track the current placeholder value for programmatic navigation
const currentPlaceholder = ref<CalendarDate>(getInitialPlaceholder());

// Update placeholder when it changes from CalendarRoot
const handlePlaceholderChange = (value: CalendarDate) => {
  if (value) {
    currentPlaceholder.value = value;
  }
};

// Handle month selection
const handleMonthSelect = (monthIndex: string) => {
  const newPlaceholder = new CalendarDate(
    currentPlaceholder.value.year,
    parseInt(monthIndex),
    1,
  );
  currentPlaceholder.value = newPlaceholder;
};

// Handle year selection
const handleYearSelect = (year: string) => {
  const newPlaceholder = new CalendarDate(
    parseInt(year),
    currentPlaceholder.value.month,
    1,
  );
  currentPlaceholder.value = newPlaceholder;
};
</script>
