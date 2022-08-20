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
    <UpsertModelLayout :errors="$attrs.errors" :model="news">
      <template #card-header> Puslapio informacija </template>
      <NewsForm
        :news="news"
        model-route="news.update"
        delete-model-route="news.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import NewsForm from "@/Components/Admin/Forms/NewsForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  news: App.Models.News;
}>();
</script>
