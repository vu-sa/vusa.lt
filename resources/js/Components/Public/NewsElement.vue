<template>
  <div class="mb-4 rounded-lg py-4 lg:mb-8">
    <header class="mb-4 flex flex-wrap items-center justify-between gap-1">
      <div>
        <h1 class="lg:mb-0">
          {{ $t("Naujienos") }}
        </h1>
        <!-- <p class="text-zinc-800 dark:text-zinc-50">
          {{
            $t("Karščiausios universiteto naujienos tiesiai iš studentų lūpų")
          }}!
        </p> -->
      </div>
      <SmartLink :href="route('newsArchive', {
        subdomain: $page.props.tenant?.subdomain ?? 'www',
        lang: $page.props.app.locale === 'lt' ? 'lt' : 'en',
        newsString: $page.props.app.locale === 'lt' ? 'naujienos' : 'news',
      })
        ">
        <div class="inline-flex items-center gap-1">
          <span>{{ $t("Daugiau") }}</span>
          <IFluentArrowRight16Regular />
        </div>
      </SmartLink>
    </header>

    <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      <HomeCard v-for="item in news" :key="item.id" :has-mini-content="false" :has-below-card="true">
        <template #mini />
        <template #below-card>
          <!-- <NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
              ataskaitinė-rinkiminė konferencija -->
          <IFluentClock20Regular />
          {{ formatStaticTime(new Date(item.publish_time), { year: "numeric", month: "long", day: "numeric" },
            $page.props.app.locale) }}
        </template>
        <template #image>
          <Link :title="item.title" :preserve-scroll="false" :href="route('news', {
            lang: item.lang,
            news: item.permalink ?? '',
            newsString: 'naujiena',
            subdomain: item.alias === 'vusa' ? 'www' : item.alias,
          })
            "><img :src="item.image"
            class="mb-1 h-52 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg md:h-40"></Link>
        </template>
        <Link :title="item.title" class="no-underline" :preserve-scroll="false" :href="route('news', {
          lang: item.lang,
          news: item.permalink ?? '',
          newsString: 'naujiena',
          subdomain: item.alias === 'vusa' ? 'www' : item.alias,
        })
          ">{{ item.title }}</Link>
      </HomeCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Link } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import HomeCard from "@/Components/Public/HomeCard.vue";
import SmartLink from "./SmartLink.vue";

defineProps<{
  news: App.Entities.News[] | null;
}>();
</script>
