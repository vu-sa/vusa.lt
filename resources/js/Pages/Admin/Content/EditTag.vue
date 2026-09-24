<template>
  <TagForm
    :post-tag
    :news
    enable-delete
    @submit:form="submitForm"
    @delete="deleteTag"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import TagForm from '@/Components/AdminForms/TagForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { TagIcon } from '@/Components/icons';

interface NewsItem {
  id: number;
  title: string;
  permalink: string;
  publish_time: string;
  lang: string;
  tenant: string;
}

const props = defineProps<{
  postTag: App.Entities.Tag;
  news?: NewsItem[];
}>();

usePageBreadcrumbs(() => {
  const name = typeof props.postTag.name === 'object' && props.postTag.name
    ? (props.postTag.name.lt || props.postTag.name.en || 'Žyma')
    : String(props.postTag.name ?? 'Žyma');
  return BreadcrumbHelpers.adminForm('Žymos', 'tags.index', name, TagIcon);
});

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<Record<string, unknown>>;
  inertiaForm.defaults();
  inertiaForm.patch(route('tags.update', props.postTag.id), { preserveScroll: true });
}

function deleteTag(): void {
  router.delete(route('tags.destroy', props.postTag.id));
}
</script>
