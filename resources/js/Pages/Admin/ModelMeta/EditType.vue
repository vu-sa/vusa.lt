<template>
  <TypeForm
    :content-types
    :type="contentType"
    enable-delete
    @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).patch(route('types.update', contentType.id), { preserveScroll: true })"
    @delete="() => router.delete(route('types.destroy', contentType.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import TypeForm from '@/Components/AdminForms/TypeForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';

const props = defineProps<{
  contentType: App.Entities.Type;
  contentTypes: App.Entities.Type[];
}>();

usePageBreadcrumbs(() => BreadcrumbHelpers.adminForm($t('Tipai'), 'types.index', getTranslatedValue(props.contentType.title)));
</script>
