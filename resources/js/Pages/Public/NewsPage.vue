<template>
  <div>
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
      <template #title>{{ article.title }} </template>
      <template #image
        ><img
          class="col-span-4 my-4 h-auto w-[65ch] rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
          :src="article.image"
      /></template>
      <em
        v-if="$page.props.otherLangPage"
        class="prose col-span-full text-sm dark:prose-invert"
        >{{ $t("Puslapis egzistuoja kita kalba") }}!
        <span class="ml-2">
          <NButton tertiary round size="small" @click="openAnotherLangNews"
            >{{ $t("Atidaryti") }}.</NButton
          >
        </span>
      </em>
      <div
        class="prose col-span-4 dark:prose-invert first-letter:float-left first-letter:mr-3 first-letter:text-7xl first-letter:font-bold"
        v-html="article.text"
      ></div>
    </NewsArticle>
    <NBackTop :right="100" :bottom="28" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t, loadLanguageAsync } from "laravel-vue-i18n";
import { NBackTop, NButton } from "naive-ui";
import { router, usePage } from "@inertiajs/vue3";

import NewsArticle from "@/Components/Public/NewsArticle.vue";

defineProps<{
  article: App.Entities.News;
}>();

const openAnotherLangNews = () => {
  let otherLangNews = usePage().props.otherLangPage;

  if (!otherLangNews) return;
  if (!otherLangNews.permalink) return;

  router.visit(
    route("news", {
      lang: otherLangNews?.lang ?? "lt",
      news: otherLangNews.permalink,
      newsString: otherLangNews.lang === "lt" ? "naujiena" : "news",
      subdomain: usePage().props.padalinys?.subdomain ?? "www",
    }),
    {
      onSuccess: () => {
        loadLanguageAsync(otherLangNews?.lang ?? "lt");
      },
    },
  );
};
</script>
