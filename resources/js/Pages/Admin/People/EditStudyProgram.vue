<template>
  <StudyProgramForm
    :study-program
    :tenants
    enable-delete
    @submit:form="submitForm"
    @delete="() => router.delete(route('studyPrograms.destroy', studyProgram.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import StudyProgramForm from '@/Components/AdminForms/StudyProgramForm.vue';
import { StudyProgramIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';

const props = defineProps<{
  studyProgram: App.Entities.StudyProgram;
  tenants: Array<App.Entities.Tenant>;
  degreeOptions?: Array<{ label: string; value: string }>;
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Studijų programos', 'studyPrograms.index', getTranslatedValue(props.studyProgram.name), StudyProgramIcon),
);

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<Record<string, unknown>>;
  inertiaForm.defaults();
  inertiaForm.patch(route('studyPrograms.update', props.studyProgram.id), { preserveScroll: true });
}
</script>
