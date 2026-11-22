<template>
  <StudyProgramForm
    remember-key="CreateStudyProgram"
    :study-program
    :tenants
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { shallowRef } from 'vue';

import StudyProgramForm from '@/Components/AdminForms/StudyProgramForm.vue';

defineProps<{
  tenants: Array<App.Entities.Tenant>;
  degreeOptions?: Array<{ label: string; value: string }>;
}>();

const studyProgram = shallowRef({
  name: { lt: '', en: '' },
  degree: '',
  tenant_id: null,
} as unknown as App.Entities.StudyProgram);

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('studyPrograms.store'));
}
</script>
