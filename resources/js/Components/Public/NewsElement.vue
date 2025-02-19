<template>
  <div v-if="news.length > 0" class="mb-4 rounded-lg py-4 lg:mb-8">
    <header class="mb-4 flex flex-wrap items-center justify-between gap-1">
      <div>
        <h2 class="lg:mb-0 text-4xl">
          {{ $t(element.json_content?.title) }}
        </h2>
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
      <SmartLink v-for="item in news" :key="item.id" prefetch :href="route('news', {
        lang: item.lang,
        news: item.permalink ?? '',
        newsString: 'naujiena',
        subdomain: $page.props.tenant?.subdomain ?? 'www',
      })">
        <NewsCard :news="item" />
      </SmartLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { usePage } from "@inertiajs/vue3";

import SmartLink from "./SmartLink.vue";
import NewsCard from "../Cards/NewsCard.vue";
import type { News } from '@/Types/contentParts';

defineProps<{
  element: News;  
}>();

const news = await fetch(
  route("api.news.tenant.index", {
    lang: usePage().props.app.locale,
    tenant: usePage().props.tenant?.alias,
  })
).then((response) => response.json());
</script>
