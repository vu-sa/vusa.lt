<template>
  <PageForm
    :page
    :available-tags
    :other-lang-pages
    :submit-url="route('pages.update', page.id)"
    submit-method="patch"
    enable-delete
    @submit:form="submitForm"
    @delete="() => router.delete(route('pages.destroy', page.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import PageForm from '@/Components/AdminForms/PageForm.vue';

const props = defineProps<{
  // `descendant_ids` is server-computed (Page::descendantIds()), not a real relation.
  page: App.Entities.Page & { descendant_ids?: number[] };
  availableTags?: App.Entities.Tag[];
  otherLangPages: App.Entities.Page[];
}>();

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<App.Entities.Page>;
  inertiaForm.defaults();
  inertiaForm.patch(route('pages.update', props.page.id), { preserveScroll: true });
}
</script>
