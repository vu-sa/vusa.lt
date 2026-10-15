<template>
  <StudySetForm
    :study-set
    :tenants="assignableTenants"
    enable-delete
    @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).patch(route('studySets.update', studySet.id))"
    @delete="() => router.delete(route('studySets.destroy', studySet.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import StudySetForm from '@/Components/AdminForms/StudySetForm.vue';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';

const props = defineProps<{
  studySet: any;
  assignableTenants: Array<{ id: number; shortname: string }>;
}>();

</script>
