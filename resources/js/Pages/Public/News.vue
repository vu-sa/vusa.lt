<template>
  <PublicLayout :title="article.title">
    <NewsArticle>
      <template #tags>
        <template v-for="tag in article.tags" v-bind:key="tag.id">
          <NButton size="tiny" style="margin: 0 0.5em 0 0.5em" round>{{
            tag.name
          }}</NButton>
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
        ><ImageForNews class="my-4 col-span-4" :src="article.image"
      /></template>
      <div
        class="prose first-letter:text-7xl first-letter:font-bold first-letter:mr-3 first-letter:float-left col-span-4"
        v-html="article.text"
      ></div>
    </NewsArticle>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "../../Layouts/PublicLayout.vue";
import NewsArticle from "../../Components/Public/NewsArticle.vue";
import ImageForNews from "../../Components/Public/ImageForNews.vue";
import { NButton, NIcon } from "naive-ui";
import { Clock20Regular } from "@vicons/fluent";

const props = defineProps({
  article: Object,
});
</script>
