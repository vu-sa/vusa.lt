<template>
  <NewsForm
    :news
    :other-lang-news
    :available-tags
    :submit-url="route('news.update', news.id)"
    submit-method="patch"
    enable-delete
    @submit:form="submitForm"
    @delete="() => router.delete(route('news.destroy', news.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import NewsForm from '@/Components/AdminForms/NewsForm.vue';
import { NewsIcon } from '@/Components/icons';

const props = defineProps<{
  news: App.Entities.News;
  otherLangNews?: App.Entities.News[];
  availableTags?: App.Entities.Tag[];
}>();

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Naujienos', 'news.index', props.news.title, NewsIcon),
);

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<App.Entities.News>;
  inertiaForm.defaults();
  inertiaForm.patch(route('news.update', props.news.id), { preserveScroll: true });
}
</script>
