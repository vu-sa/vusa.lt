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
      <SmartLink prefetch v-for="item in news" :key="item.id" :href="route('news', {
        lang: item.lang,
        news: item.permalink ?? '',
        newsString: 'naujiena',
        subdomain: item.alias === 'vusa' ? 'www' : item.alias,
      })">
      <NewsCard :news="item" />
      </SmartLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Link } from "@inertiajs/vue3";

import SmartLink from "./SmartLink.vue";
import NewsCard from "../Cards/NewsCard.vue";

defineProps<{
  news: App.Entities.News[] | null;
}>();
</script>
