<template>
  <Head :title="article.title"></Head>
  <NBackTop :right="100" />
  <FadeTransition appear>
    <NewsArticle>
      <template #tags>
        <template v-for="tag in article.tags" :key="tag.id">
          <NButton size="tiny" round>{{ tag.name }}</NButton>
        </template>
      </template>
      <template #author>
        <!-- <img class="w-8 mr-1" src="/logos/vusa.lin.hor.svg" /> -->
        <span>{{ article.padalinys }}</span>
      </template>
      <template #time>
        <!-- <NIcon class="mr-2" size="16"> <Clock20Regular /> </NIcon> -->
        {{ article.publish_time }}</template
      >
      <template #title
        >{{ article.title }}
        <NButton v-if="$page.props.user" text @click="editNews"
          ><NIcon size="28" :component="DocumentEdit24Regular" /></NButton
      ></template>
      <template #image
        ><img
          class="col-span-4 my-4 h-auto w-[65ch] rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
          :src="article.image"
      /></template>
      <em
        v-if="article.other_lang_id"
        class="prose col-span-full text-sm dark:prose-invert"
        >{{ $t("Puslapis egzistuoja kita kalba") }}!
        <span class="ml-2">
          <NButton tertiary round size="small" @click="openAnotherLangNews"
            >{{ $t("Atidaryti") }}.</NButton
          >
        </span>
      </em>
      <div
        class="prose col-span-4 first-letter:float-left first-letter:mr-3 first-letter:text-7xl first-letter:font-bold dark:prose-invert"
        v-html="article.text"
      ></div>
    </NewsArticle>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { DocumentEdit24Regular } from "@vicons/fluent";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { NBackTop, NButton, NIcon } from "naive-ui";
import route from "ziggy-js";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";
import NewsArticle from "@/Components/Public/NewsArticle.vue";

const props = defineProps<{
  article: App.Models.News;
  otherLangNews: App.Models.News | null;
}>();

const editNews = () => {
  Inertia.visit(route("news.edit", { id: props.article.id }));
};

const openAnotherLangNews = () => {
  window.open(
    route("news", {
      lang: props.otherLangNews.lang,
      newsString: props.otherLangNews.lang === "lt" ? "naujiena" : "news",
      padalinys: usePage().props.value.alias,
      permalink: props.otherLangNews.permalink,
    }),
    "_blank"
  );
};
</script>
