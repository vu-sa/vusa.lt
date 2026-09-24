<template>
  <TypeForm remember-key="CreateType" :content-types :type @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).post(route('types.store'))" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import TypeForm from '@/Components/AdminForms/TypeForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { typeTemplate as type } from '@/Types/formTemplates';

defineProps<{
  contentTypes: App.Entities.Type[];
}>();

usePageBreadcrumbs(BreadcrumbHelpers.adminForm($t('Tipai'), 'types.index', $t('Naujas turinio tipas')));
</script>
