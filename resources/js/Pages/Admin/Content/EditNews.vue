<template>
  <PageContent :title="news.title" :back-url="route('news.index')" :heading-icon="Icons.NEWS">
    <template #header>
      {{ news.title }}
    </template>
    <UpsertModelLayout>
      <template #card-header>
        Puslapio informacija
      </template>
      <NewsForm :news :other-lang-news enable-delete
        @submit:form="(form) => form.patch(route('news.update', news.id), { preserveScroll: true })"
        @delete="() => router.delete(route('news.destroy', news.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import NewsForm from "@/Components/AdminForms/NewsForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  news: App.Entities.News;
  otherLangNews: App.Entities.News[] | null;
}>();
</script>
