<template>
  <PageContent :title="`${dutiable.duty?.name || 'Pareigybė'} (${dutiable.dutiable?.name || 'Asmuo'})`"
    :back-url="route('users.edit', dutiable.dutiable?.id || '')" :heading-icon="Icons.DUTY">
    <UpsertModelLayout>
      <DutiableForm :dutiable :study-programs enable-delete
        @submit:form="(form: any) => form.patch(route('dutiables.update', dutiable.id), { preserveScroll: true })"
        @delete="() => router.delete(route('dutiables.destroy', dutiable.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import DutiableForm from '@/Components/AdminForms/DutiableForm.vue';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

defineProps<{
  dutiable: App.Entities.Dutiable;
  studyPrograms: App.Entities.StudyProgram[];
}>();
</script>
