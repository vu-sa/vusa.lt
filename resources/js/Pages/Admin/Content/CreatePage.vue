<template>
  <AdminLayout title="Nauja naujiena" :back-url="route('pages.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="page">
      <template #card-header> Puslapio informacija </template>
      <PageForm :page="page" model-route="pages.store" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import PageForm from "@/Components/Admin/Forms/PageForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const page = {
  title: "",
  permalink: "",
  lang: "lt",
  other_lang_page: null,
  text: "",
};
</script>
