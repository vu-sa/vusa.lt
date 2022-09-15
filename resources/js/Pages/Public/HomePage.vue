<template>
  <Head>
    <title>Pagrindinis</title>
  </Head>

  <FadeTransition appear>
    <div>
      <EventCalendar
        :is-theme-dark="isThemeDark"
        :show-photos="true"
        :calendar="calendar"
      /></div
  ></FadeTransition>

  <FadeTransition v-if="mainPage.length > 0" appear>
    <div class="mt-8">
      <MainLinks :main-page="mainPage" /></div
  ></FadeTransition>

  <FadeTransition v-if="news.length > 0" appear>
    <div class="mt-4"><NewsElement :news="news" /></div>
  </FadeTransition>

  <FadeTransition v-if="$page.props.locale === 'lt'" appear>
    <div><SummerCamps :is-theme-dark="isThemeDark" /></div
  ></FadeTransition>

  <FadeTransition appear>
    <div><YearReport2022 :is-theme-dark="isThemeDark" /></div>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";
import { onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import EventCalendar from "@/Components/Public/FullWidth/EventCalendar.vue";
import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";
import MainLinks from "@/Components/Public/FullWidth/MainLinks.vue";
import NewsElement from "@/Components/Public/NewsElement.vue";
import SummerCamps from "@/Components/Public/FullWidth/SummerCamps.vue";
import YearReport2022 from "@/Components/Public/FullWidth/YearReport2022.vue";

defineProps<{
  news: Array<App.Models.News>;
  mainPage: Array<App.Models.MainPage>;
  calendar: Array<App.Models.Calendar>;
}>();

const isThemeDark = ref(isDarkMode());

onMounted(() => {
  updateDarkMode(isThemeDark);
});
</script>
