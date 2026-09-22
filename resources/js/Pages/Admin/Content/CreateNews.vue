<template>
  <NewsForm
    :news
    :available-tags
    :assignable-tenants
    remember-key="CreateNews"
    :submit-url="route('news.store')"
    submit-method="post"
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import NewsForm from '@/Components/AdminForms/NewsForm.vue';
import { newsTemplate as news } from '@/Types/formTemplates';
import { NewsIcon } from '@/Components/icons';

defineProps<{
  availableTags?: App.Entities.Tag[];
  assignableTenants: App.Entities.Tenant[];
}>();

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Naujienos', 'news.index', 'Nauja naujiena', NewsIcon),
);

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('news.store'));
}
</script>
