<template>
  <PublicLayout :title="article.title">
    <NBackTop :right="100" />
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
      <template #title>{{ article.title }}</template>
      <template #image
        ><img
          class="my-4 col-span-4 rounded-sm shadow-md hover:shadow-lg duration-200 h-auto w-[65ch] object-cover"
          :src="article.image"
      /></template>
      <div class="col-span-full">
        <NButton v-if="$page.props.user" text @click="editNews"
          ><NIcon size="40"
            ><DocumentEdit24Regular></DocumentEdit24Regular></NIcon
        ></NButton>
      </div>
      <div
        class="prose first-letter:text-7xl first-letter:font-bold first-letter:mr-3 first-letter:float-left col-span-4"
        v-html="article.text"
      ></div>
    </NewsArticle>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Clock20Regular, DocumentEdit24Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NBackTop, NButton, NIcon } from "naive-ui";
import NewsArticle from "../../Components/Public/NewsArticle.vue";
import PublicLayout from "../../Layouts/PublicLayout.vue";

const props = defineProps({
  article: Object,
});

const editNews = () => {
  Inertia.visit(route("news.edit", { id: props.article.id }));
};
</script>
