<template>
  <Calendar :is-dark="isThemeDark">
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

defineProps<{
  isThemeDark: boolean;
}>();
</script>
