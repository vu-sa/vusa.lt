<template>
  <PageContent title="Nauja naujiena" :back-url="route('news.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="news">
      <template #card-header> Puslapio informacija </template>
      <NewsForm :news="news" model-route="news.store" />
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

import NewsForm from "@/Components/Admin/Forms/NewsForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const news = {
  title: "",
  permalink: "",
  lang: "lt",
  other_lang_id: null,
  text: "",
  short: "",
  publish_time: null,
  draft: false,
  image: null,
  image_author: null,
};
</script>
