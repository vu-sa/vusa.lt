<template>
  <PageContent title="Nauja naujiena" :back-url="route('news.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="news">
      <template #card-header> Puslapio informacija </template>
      <NewsForm :news="news" model-route="news.store" />
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


import NewsForm from "@/Components/AdminForms/NewsForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

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
