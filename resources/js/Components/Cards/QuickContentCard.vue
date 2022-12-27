<template>
  <NConfigProvider :theme-overrides="themeOverrides"
    ><NCard
      :segmented="{
        footer: 'soft',
      }"
    >
      <div class="mt-2 inline-flex items-center gap-2">
        <NIcon :size="45" :component="CalendarClock20Regular"></NIcon>

        <span class="text-5xl font-bold">
          <NNumberAnimation
            :from="0"
            :to="getDaysDifference(lastDoing.date)"
          ></NNumberAnimation>
        </span>
      </div>
      <p class="mt-4">
        Paskutinis posėdis vyko
        <span class="font-bold">{{ getMMMMDD(lastDoing.date) }}</span>
      </p>
      <template #footer>
        <div class="flex items-center justify-between">
          <slot name="action-button" />
          <NButton size="small" text
            ><template #icon
              ><NIcon :component="Info24Regular"></NIcon></template
          ></NButton>
        </div> </template></NCard
  ></NConfigProvider>
</template>

<script setup lang="tsx">
import {
  BookExclamationMark20Filled,
  CalendarClock20Regular,
  Info24Regular,
  PeopleTeamAdd24Filled,
  PuzzlePiece20Regular,
  Question24Regular,
} from "@vicons/fluent";
import {
  NButton,
  NCard,
  NConfigProvider,
  NDivider,
  NIcon,
  NNumberAnimation,
  NStatistic,
} from "naive-ui";
// import { ref } from "vue";
import { getMMMMDD } from "@/Composables/getRelativeTime";
import HelpTextModal from "@/Components/Buttons/HelperButtons/HelpTextModal.vue";

// import { isDarkMode } from "@/Composables/darkMode";
import getRelativeTime, {
  getDaysDifference,
} from "@/Composables/getRelativeTime";

defineProps<{
  lastDoing: App.Models.Doing;
}>();

const themeOverrides = {
  //   Card: {
  //     borderColor: "#bd283520",
  //   },
};
const darkThemeOverrides = {
  Card: {
    color: "#bd2835",
  },
};
</script>
