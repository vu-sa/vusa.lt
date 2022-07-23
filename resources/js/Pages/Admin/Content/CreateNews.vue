<template>
  <AdminLayout title="Nauja naujiena" :back-url="route('news.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="news">
      <template #card-header> Puslapio informacija </template>
      <NewsForm :news="news" model-route="news.create" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import NewsForm from "@/components/Admin/Forms/NewsForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

const news = {
  title: "",
  permalink: "",
  lang: "lt",
  other_lang_news: null,
  text: "",
  short: "",
  publish_time: null,
  draft: false,
  image: null,
  image_author: null,
};
</script>
