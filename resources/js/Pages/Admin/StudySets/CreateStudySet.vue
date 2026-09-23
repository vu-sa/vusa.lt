<template>
  <StudySetForm
    remember-key="CreateStudySet"
    :study-set
    :tenants="assignableTenants"
    @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).post(route('studySets.store'))"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import StudySetForm from '@/Components/AdminForms/StudySetForm.vue';
import { StudySetIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

defineProps<{
  assignableTenants: Array<{ id: number; shortname: string }>;
}>();

const studySet = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  order: 0,
  is_visible: true,
  tenant_id: null,
  courses: [],
  reviews: [],
};

usePageBreadcrumbs(BreadcrumbHelpers.adminForm($t('Individualių studijų komplektai'), 'studySets.index', $t('Naujas komplektas'), StudySetIcon));
</script>
