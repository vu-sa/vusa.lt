<template>
  <Head>
    <title>Pagrindinis</title>
  </Head>

  <FadeTransition appear>
    <div v-if="$page.props.locale === 'lt'">
      <EventCalendar :calendar="calendar" /></div
  ></FadeTransition>

  <FadeTransition appear>
    <template v-if="$page.props.locale === 'lt'">
      <MainLinks v-if="mainPage.length > 0" :main-page="mainPage" /></template
  ></FadeTransition>

  <FadeTransition appear>
    <NewsElement
      v-if="$page.props.locale === 'lt' && news.length > 0"
      :news="news"
    />
  </FadeTransition>

  <FadeTransition appear>
    <div v-if="$page.props.locale === 'lt'"><SummerCamps /></div
  ></FadeTransition>

  <FadeTransition appear>
    <div><YearReport2022 /></div>
  </FadeTransition>

  <FadeTransition appear>
    <template v-if="$page.props.locale === 'en'">
      <MainLinks v-if="mainPage.length > 0" :main-page="mainPage" /></template
  ></FadeTransition>

  <FadeTransition appear>
    <NewsElement
      v-if="$page.props.locale === 'en' && news.length > 0"
      :news="news"
    />
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
</script>
