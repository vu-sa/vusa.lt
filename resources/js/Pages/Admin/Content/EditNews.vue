<template>
  <AdminLayout :title="news.title" :back-url="route('news.index')">
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
    </template>
    <UpsertModelLayout :errors="$attrs.errors" :model="news">
      <template #card-header> Puslapio informacija </template>
      <NewsForm
        :news="news"
        model-route="news.update"
        delete-model-route="news.destroy"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import NewsForm from "@/components/Admin/Forms/NewsForm.vue";
import PreviewModelButton from "@/components/Admin/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  news: App.Models.News;
}>();
</script>
