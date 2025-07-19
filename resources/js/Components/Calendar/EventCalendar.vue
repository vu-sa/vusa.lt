<template>
  <div class="my-calendar">
    <Calendar :initial-page :is-dark :attributes="calendarAttributes" :locale="{
      id: $page.props.app.locale,
      firstDayOfWeek: 2,
      masks: { weekdays: 'WW' },
    }" color="red" class="shadow-xl">
      <template #day-popover="{ attributes, dayTitle }">
        <div class="max-w-md">
          <div class="mb-1 text-center text-xs font-semibold text-gray-300 dark:text-zinc-700">
            {{ dayTitle }}
          </div>
          <PopoverRow v-for="attr in attributes" :key="attr.key" :attribute="attr">
            <div class="inline-flex items-center gap-2">
              <a target="_blank" :href="route('calendar.event', {
                calendar: attr.key,
                lang: $page.props.app.locale,
              })
                ">{{ attr.popover.label }}</a>
              <NConfigProvider class="flex h-fit items-center justify-center" :theme="isDark ? undefined : darkTheme">
                <div class="my-auto flex items-center justify-center">
                  <NButton text tag="a" target="_blank" :href="attr.customData.googleLink" color="rgb(189, 40, 53)"
                    size="tiny">
                    <IMdiGoogle />
                  </NButton>
                </div>
              </NConfigProvider>
            </div>
          </PopoverRow>
        </div>
      </template>
    </Calendar>
  </div>
</template>

<script setup lang="tsx">
import "v-calendar/style.css";
import { Calendar, PopoverRow } from "v-calendar";
import { darkTheme } from "naive-ui";
import { useDark } from "@vueuse/core";
import { computed } from "vue";
import type { PageAddress } from "v-calendar/dist/types/src/utils/page";

const props = defineProps<{
  calendarEvents: App.Entities.Calendar[];
  locale: string;
}>();

const initialPage: PageAddress = {
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  day: new Date().getDate(),
};

const isDark = useDark();

// Optimize date comparison function
const isSameDay = (date1: string, date2: string | null) => {
  if (!date2) return true;
  
  const parsedDate1 = new Date(date1);
  const parsedDate2 = new Date(date2);

  return (
    parsedDate1.getFullYear() === parsedDate2.getFullYear() &&
    parsedDate1.getMonth() === parsedDate2.getMonth() &&
    parsedDate1.getDate() === parsedDate2.getDate()
  );
};

// Create a category color map for better performance
const categoryColorMap = {
  "freshmen-camps": "yellow",
  "vu-sa-conferences": "yellow", 
  "grey": "gray",
} as const;

// Memoize calendar attributes calculation for performance
const calendarAttributes = computed(() => {
  const attributes = props.calendarEvents.map((event) => {
    const eventColor = categoryColorMap[event.category?.alias as keyof typeof categoryColorMap] || "red";
    
    const startDate = new Date(event.date);
    const endDate = event.end_date ? new Date(event.end_date) : null;
    
    return {
      dates: endDate ? { start: startDate, end: endDate } : startDate,
      [isSameDay(event.date, event.end_date) ? "dot" : "highlight"]: eventColor,
      popover: {
        label: event.title,
        isInteractive: true,
      },
      key: event.id,
      customData: { googleLink: event.googleLink },
    };
  });

  // Add today's indicator
  attributes.push({
    dates: new Date(),
    highlight: { color: "red", fillMode: "outline" },
    order: 1,
  });

  return attributes;
});
</script>

<style scoped>
.my-calendar :deep(.vc-container.vc-dark) {
  background-color: #29292e;
}

.my-calendar :deep(button.vc-arrow) {
  background-color: transparent;
}

.vc-container {
  font-family: "Inter", sans-serif !important;
  border: 0 !important;
}

.my-calendar :deep(.vc-dot) {
  box-shadow: 0px 0px 1px 0px rgba(0, 0, 0, 0.6);
}
</style>
