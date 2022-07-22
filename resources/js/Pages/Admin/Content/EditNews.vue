<template>
  <AdminLayout :title="news.title" :back-url="route('news.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="news">
      <template #card-header class="mb-0"> Puslapio informacija </template>
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
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  news: App.Models.News;
}>();
</script>
