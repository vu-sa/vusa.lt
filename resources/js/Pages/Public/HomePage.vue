<template>
  <Head>
    <title>Pagrindinis</title>
  </Head>

  <FadeTransition appear>
    <div v-if="$page.props.locale === 'lt'"><SummerCamps /></div
  ></FadeTransition>

  <FadeTransition appear>
    <div v-if="$page.props.locale === 'lt'">
      <MainLinks :main-page="mainPage" /></div
  ></FadeTransition>

  <FadeTransition appear>
    <NewsElement v-if="$page.props.locale === 'lt'">
      <HomeCard
        v-for="item in news"
        :key="item.id"
        :has-mini-content="false"
        :has-below-card="true"
      >
        <template #mini> </template>
        <template #below-card>
          <!-- <NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
              ataskaitinė-rinkiminė konferencija -->
          <NIcon class="mr-2" size="20"> <Clock20Regular /> </NIcon
          >{{ item.publish_time }}
        </template>
        <template #image>
          <Link
            :href="
              route('news', {
                lang: item.lang,
                newsString: 'naujiena',
                padalinys: item.alias,
                permalink: item.permalink,
              })
            "
            ><img
              :src="item.image"
              class="mb-1 h-40 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
          /></Link>
        </template>
        <Link
          :href="
            route('news', {
              lang: item.lang,
              newsString: 'naujiena',
              padalinys: item.alias,
              permalink: item.permalink,
            })
          "
          >{{ item.title }}</Link
        >
      </HomeCard>
    </NewsElement>
  </FadeTransition>

  <FadeTransition appear>
    <div><YearReport2022 /></div>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Clock20Regular } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import route from "ziggy-js";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";
import HomeCard from "@/Components/Public/HomeCard.vue";
import MainLinks from "@/Components/Public/FullWidth/MainLinks.vue";
import NewsElement from "@/Components/Public/NewsElement.vue";
import SummerCamps from "@/Components/Public/FullWidth/SummerCamps.vue";
import YearReport2022 from "@/Components/Public/FullWidth/YearReport2022.vue";

defineProps<{
  news: Array<App.Models.News>;
  mainPage: Array<App.Models.MainPage>;
}>();
</script>
