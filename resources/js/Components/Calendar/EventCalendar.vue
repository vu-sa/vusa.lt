<template>
  <Calendar
    :is-dark="isThemeDark"
    :attributes="calendarAttributes"
    color="red"
    class="shadow-xl"
  >
    <template #day-popover="{ attributes, dayTitle }">
      <div class="max-w-md">
        <div
          class="mb-1 text-center text-xs font-semibold text-gray-300 dark:text-zinc-700"
        >
          {{ dayTitle }}
        </div>
        <PopoverRow
          v-for="attr in attributes"
          :key="attr.key"
          :attribute="attr"
        >
          <div class="inline-flex items-center gap-2">
            <a
              target="_blank"
              :href="
                route('calendar.event', {
                  calendar: attr.key,
                  lang: $page.props.app.locale,
                })
              "
              >{{ attr.popover.label }}</a
            >
            <NConfigProvider
              class="flex h-fit items-center justify-center"
              :theme="isThemeDark ? undefined : darkTheme"
            >
              <div class="my-auto flex items-center justify-center">
                <NButton
                  text
                  tag="a"
                  target="_blank"
                  :href="attr.customData.googleLink"
                  color="rgb(189, 40, 53)"
                  size="tiny"
                  ><NIcon :component="Google"
                /></NButton>
              </div>
            </NConfigProvider>
          </div>
        </PopoverRow>
      </div>
    </template>
  </Calendar>
</template>

<script setup lang="tsx">
import { Calendar, PopoverRow } from "v-calendar";
import { Google } from "@vicons/fa";
import { NButton, NConfigProvider, NIcon, darkTheme } from "naive-ui";

const props = defineProps<{
  isThemeDark: boolean;
  calendarEvents: App.Entities.Calendar[];
}>();

// check if event date and end date is on the same day
const isSameDay = (date1: string, date2: string) => {
  if (date2 === null) {
    return true;
  }

  let parsedDate1 = new Date(date1);
  let parsedDate2 = new Date(date2.replace(/-/g, "/"));

  return (
    parsedDate1.getFullYear() === parsedDate2.getFullYear() &&
    parsedDate1.getMonth() === parsedDate2.getMonth() &&
    parsedDate1.getDate() === parsedDate2.getDate()
  );
};

const calendarAttributes = props.calendarEvents.map((event) => {
  let eventColor = event.category;

  switch (event.category) {
    case "freshmen-camps":
      eventColor = "orange";
      break;

    case "grey":
      eventColor = "gray";
      break;

    default:
      eventColor = "red";
      break;
  }

  let calendarAttrObject = {
    dates: event.end_date
      ? {
          start: new Date(event.date),
          end: new Date(event.end_date.replace(/-/g, "/")),
        }
      : new Date(event.date),
    [isSameDay(event.date, event.end_date) ? "dot" : "highlight"]: eventColor,
    popover: {
      label: event.title,
      isInteractive: true,
    },
    key: event.id,
    customData: { googleLink: event.googleLink },
  };
  return calendarAttrObject;
});

// add today to the calendar
calendarAttributes.push({
  dates: new Date(),
  highlight: { color: "red", fillMode: "outline" },
  order: 1,
});
</script>

<style scoped>
.vc-container {
  font-family: "Inter", sans-serif !important;
  border: 0 !important;
}

.vc-container.vc-is-dark {
  background-color: rgb(55, 55, 62);
}
</style>