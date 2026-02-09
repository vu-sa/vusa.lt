<template>
  <PageContent :title :heading-icon="Icons.STUDY_PROGRAM">
    <UpsertModelLayout>
      <StudyProgramForm :study-program :tenants @submit:form="(form: any) => form.patch(route('studyPrograms.update', studyProgram.id))"
        @delete="() => router.delete(route('studyPrograms.destroy', studyProgram.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import StudyProgramForm from '@/Components/AdminForms/StudyProgramForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { useTranslatedTitle } from '@/Composables/useTranslatedTitle';

const props = defineProps<{
  studyProgram: App.Entities.StudyProgram;
  tenants: Array<App.Entities.Tenant>;
  degreeOptions: Array<{ label: string; value: string }>;
}>();

const title = useTranslatedTitle('Redaguoti studijų programą', props.studyProgram.name);
</script>
