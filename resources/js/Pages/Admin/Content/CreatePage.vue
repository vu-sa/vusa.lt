<template>
  <PageContent title="Naujas puslapis" :back-url="route('pages.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="page">
      <template #card-header> Puslapio informacija </template>
      <PageForm :page="page" model-route="pages.store" />
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
import route from "ziggy-js";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PageForm from "@/Components/AdminForms/PageForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const page = {
  title: "",
  permalink: "",
  lang: "lt",
  other_lang_page: null,
  text: "",
};
</script>
