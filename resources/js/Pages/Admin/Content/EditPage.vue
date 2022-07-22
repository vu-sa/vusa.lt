<template>
  <AdminLayout :title="page.title" :back-url="route('pages.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="page">
      <template #card-header> Puslapio informacija </template>
      <PageForm
        :page="page"
        model-route="duties.update"
        delete-model-route="duties.destroy"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import PageForm from "@/components/Admin/Forms/PageForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  page: App.Models.Page;
}>();
</script>
