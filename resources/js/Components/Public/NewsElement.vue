<template>
  <div
    class="mx-8 mb-4 rounded-lg bg-white p-4 text-gray-800 shadow-lg dark:bg-zinc-800 dark:text-white lg:mx-16 lg:mb-8 lg:px-8"
  >
    <h1 class="lg:mb-0">{{ $t("Naujienos") }}</h1>
    <div class="mb-4 flex flex-wrap justify-between gap-1">
      <p>
        {{
          $t("Karščiausios universiteto naujienos tiesiai iš studentų lūpų")
        }}!
      </p>
      <Link
        v-if="$page.props.locale === 'lt'"
        :href="route('main.newsArchive', { lang: 'lt' })"
        >Daugiau →</Link
      >
    </div>
    <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
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
            :preserve-scroll="false"
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
          :preserve-scroll="false"
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Clock20Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import route from "ziggy-js";

import HomeCard from "@/Components/Public/HomeCard.vue";

defineProps<{
  news: App.Models.News[] | null;
}>();
</script>
