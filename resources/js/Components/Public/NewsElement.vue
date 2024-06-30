<template>
  <div class="mb-4 rounded-lg py-4 lg:mb-8">
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
            subdomain: $page.props.padalinys?.subdomain ?? 'www',
          })
        "
        ><div class="inline-flex items-center gap-1">
          <span>Daugiau</span><NIcon :component="ArrowRight16Regular"></NIcon>
        </div>
      </Link>
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
          >{{ formatStaticTime(new Date(item.publish_time), { year: "numeric", month: "long", day: "numeric" }, $page.props.app.locale) }}
        </template>
        <template #image>
          <Link :title="item.title"
            :preserve-scroll="false"
            :href="
              route('news', {
                lang: item.lang,
                news: item.permalink ?? '',
                newsString: 'naujiena',
                subdomain: item.alias === 'vusa' ? 'www' : item.alias,
              })
            "
            ><img
              :src="item.image"
              class="mb-1 h-52 md:h-40 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
          /></Link>
        </template>
        <Link :title="item.title" class="no-underline"
          :preserve-scroll="false"
          :href="
            route('news', {
              lang: item.lang,
              news: item.permalink ?? '',
              newsString: 'naujiena',
              subdomain: item.alias === 'vusa' ? 'www' : item.alias,
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
import { ArrowRight16Regular, Clock20Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/vue3";
import { NIcon } from "naive-ui";

import HomeCard from "@/Components/Public/HomeCard.vue";
import { formatStaticTime } from "@/Utils/IntlTime";

defineProps<{
  news: App.Entities.News[] | null;
}>();
</script>
