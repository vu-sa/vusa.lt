<template>
  <PageContent :title="page.title" :back-url="route('pages.index')" :heading-icon="Icons.PAGE">
    <template #header>
      {{ page.title }}
    </template>
    <UpsertModelLayout>
      <template #card-header>
        <span>Puslapio informacija</span>
      </template>
      <PageForm :categories :page :other-lang-pages model-route="pages.update" enable-delete
        @submit:form="(form) => form.patch(route('pages.update', page.id), { preserveScroll: true })"
        @delete="() => router.delete(route('pages.destroy', page.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PageForm from "@/Components/AdminForms/PageForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  categories: App.Entities.Category[];
  page: App.Entities.Page;
  otherLangPages: App.Entities.Page[];
}>();
</script>
