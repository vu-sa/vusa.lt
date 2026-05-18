<template>
  <PageContent :title="page.title" :back-url="route('pages.index')" :heading-icon="PageIcon">
    <template #header>
      {{ page.title }}
    </template>
    <UpsertModelLayout>
      <template #card-header>
        <span>Puslapio informacija</span>
      </template>
      <PageForm
        :categories
        :page
        :other-lang-pages
        :submit-url="route('pages.update', page.id)"
        submit-method="patch"
        enable-delete
        @submit:form="(form) => form.patch(route('pages.update', page.id), { preserveScroll: true })"
        @delete="() => router.delete(route('pages.destroy', page.id))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import PageForm from '@/Components/AdminForms/PageForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { PageIcon } from '@/Components/icons';

defineProps<{
  categories: App.Entities.Category[];
  page: App.Entities.Page;
  otherLangPages: App.Entities.Page[];
}>();
</script>
