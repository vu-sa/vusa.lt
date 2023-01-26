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

  <!-- <FadeTransition v-if="$page.props.locale === 'lt'">
    <div class="mt-8">
      <BannerCarousel :banners="banners" />
    </div>
  </FadeTransition> -->

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
import { defineAsyncComponent } from "vue";
import { onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import EventCalendar from "@/Components/Public/FullWidth/EventCalendar.vue";
import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

defineProps<{
  news: Array<App.Models.News>;
  banners: Array<App.Models.Banner>;
  mainPage: Array<App.Models.MainPage>;
  calendar: Array<App.Models.Calendar>;
}>();

const isThemeDark = ref(isDarkMode());

const MainLinks = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/MainLinks.vue")
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

const YearReport2022 = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/YearReport2022.vue")
);

updateDarkMode(isThemeDark);

onMounted(() => {
  // updateDarkMode(isThemeDark);
});
</script>
