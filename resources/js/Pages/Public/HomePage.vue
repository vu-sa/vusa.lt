<template>
  <Head>
    <title>Pagrindinis</title>
  </Head>

  <EventCalendar
    :is-theme-dark="isThemeDark"
    :show-photos="true"
    :calendar="calendar"
  />

  <div v-if="mainPage.length > 0" class="mt-8">
    <MainLinks :main-page="mainPage" />
  </div>

  <!-- <FadeTransition v-if="$page.props.locale === 'lt'">
            <div class="mt-8">
              <BannerCarousel :banners="banners" />
            </div>
          </FadeTransition> -->

  <div v-if="news.length > 0" class="mt-4"><NewsElement :news="news" /></div>

  <SummerCamps
    v-if="$page.props.app.locale === 'lt'"
    :is-theme-dark="isThemeDark"
  />

  <YearReport2022 :is-theme-dark="isThemeDark" />
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { defineAsyncComponent } from "vue";
import { onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";

defineProps<{
  news: Array<App.Entities.News>;
  banners: Array<App.Entities.Banner>;
  mainPage: Array<App.Entities.MainPage>;
  calendar: Array<App.Entities.Calendar>;
}>();

const isThemeDark = ref(isDarkMode());

const MainLinks = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/MainLinks.vue")
);

const EventCalendar = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/EventCalendar.vue")
);

// const BannerCarousel = defineAsyncComponent(
// eslint-disable-next-line no-secrets/no-secrets
//   () => import("@/Components/Public/FullWidth/BannerCarousel.vue")
// );

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

onMounted(() => {
  // updateDarkMode(isThemeDark);
});
</script>
