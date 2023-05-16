<template>
  <Head>
    <title>Pagrindinis</title>
  </Head>

  <YearReport2022 :is-theme-dark="isThemeDark" />

  <div v-if="news.length > 0" class="mx-auto mt-2 max-w-7xl">
    <NewsElement :news="news" />
  </div>

  <div v-if="mainPage.length > 0" class="mx-auto mt-2 max-w-7xl">
    <MainLinks :main-page="mainPage" />
  </div>

  <EventCalendar
    :is-theme-dark="isThemeDark"
    :show-photos="true"
    :calendar="calendar"
    :upcoming4-events="upcoming4Events"
  />

  <SummerCamps
    v-if="$page.props.app.locale === 'lt'"
    :is-theme-dark="isThemeDark"
  />

  <div v-if="$page.props.banners" class="mx-auto mt-8 max-w-7xl">
    <BannerCarousel :banners="$page.props.banners" />
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { defineAsyncComponent } from "vue";
import { ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";

defineProps<{
  news: Array<App.Entities.News>;
  mainPage: Array<App.Entities.MainPage>;
  calendar: Array<App.Entities.Calendar>;
  upcoming4Events: Array<App.Entities.Calendar>;
}>();

const isThemeDark = ref(isDarkMode());

const MainLinks = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/MainLinks.vue")
);

const EventCalendar = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/EventCalendarElement.vue")
);

const BannerCarousel = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/BannerCarousel.vue")
);

const NewsElement = defineAsyncComponent(
  () => import("@/Components/Public/NewsElement.vue")
);

const SummerCamps = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/SummerCamps.vue")
);
// yearreport2022 make unsuspensible
const YearReport2022 = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/YearReport2022.vue")
);

updateDarkMode(isThemeDark);
</script>
