<template>
  <PageContent :title="news.title" :back-url="route('news.index')">
    <template #header>
      {{ news.title }}
      <PreviewModelButton
        main-route="main.news"
        padalinys-route="padalinys.news"
        :main-props="{
          lang: news.lang,
          newsString: 'naujiena',
          permalink: news.permalink,
        }"
        :padalinys-props="{
          newsString: 'naujiena',
          lang: news.lang,
          permalink: news.permalink,
          padalinys: news.padalinys?.alias,
        }"
        :padalinys-shortname="news.padalinys?.shortname"
      ></PreviewModelButton>
      <DeleteModelButton
        class="flex-end"
        :form="news"
        size="small"
        model-route="news.destroy"
      ></DeleteModelButton>
    </template>
    <UpsertModelLayout :errors="$page.props.errors" :model="news">
      <template #card-header> Puslapio informacija </template>
      <NewsForm
        :news="news"
        :other-lang-news="otherLangNews"
        model-route="news.update"
        delete-model-route="news.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">


import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import NewsForm from "@/Components/AdminForms/NewsForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  news: App.Entities.News;
  otherLangNews: App.Entities.News[] | null;
}>();
</script>
