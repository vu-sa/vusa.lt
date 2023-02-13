<template>
  <div class="mx-8 mb-4 rounded-lg p-4 lg:mx-16 lg:mb-8 lg:px-8">
    <header class="mb-4 flex flex-wrap items-center justify-between gap-1">
      <div>
        <h1 class="lg:mb-0">{{ $t("Naujienos") }}</h1>
        <!-- <p class="text-zinc-800 dark:text-zinc-50">
          {{
            $t("Karščiausios universiteto naujienos tiesiai iš studentų lūpų")
          }}!
        </p> -->
      </div>
      <Link
        v-if="$page.props.app.locale === 'lt'"
        :href="
          route('newsArchive', {
            padalinys:
              $page.props.alias === 'vusa' ? 'www' : $page.props.alias ?? 'www',
          })
        "
        ><NButton text icon-placement="right"
          >Daugiau<template #icon
            ><NIcon
              :component="ArrowRight12Regular"
            ></NIcon></template></NButton
      ></Link>
    </header>

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
                padalinys: item.alias === 'vusa' ? 'www' : item.alias,
                permalink: item.permalink ?? '',
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
              padalinys: item.alias === 'vusa' ? 'www' : item.alias,
              permalink: item.permalink ?? '',
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
import { ArrowRight12Regular, Clock20Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/vue3";
import { NButton, NIcon } from "naive-ui";

import HomeCard from "@/Components/Public/HomeCard.vue";

defineProps<{
  news: App.Entities.News[] | null;
}>();
</script>
