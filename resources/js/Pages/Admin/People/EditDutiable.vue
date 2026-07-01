<template>
  <PageContent :title="`${dutiable.duty?.name || 'Pareigybė'} (${dutiable.dutiable?.name || 'Asmuo'})`"
    :back-url="route('users.edit', dutiable.dutiable?.id || '')" :heading-icon="DutyIcon">
    <UpsertModelLayout>
      <DutiableForm :dutiable :study-programs enable-delete
        @submit:form="onSubmit"
        @delete="onDelete" />
    </UpsertModelLayout>
    <AccessChangeWarningDialog :open="open" :report="report"
      @update:open="open = $event" @confirm="confirm" @cancel="cancel" />
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import DutiableForm from '@/Components/AdminForms/DutiableForm.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import { DutyIcon } from '@/Components/icons';

const props = defineProps<{
  dutiable: App.Entities.Dutiable;
  studyPrograms: App.Entities.StudyProgram[];
}>();

const { report, open, guardedSubmit, confirm, cancel } = useAccessChangeGuard();

const onSubmit = (form: any) =>
  guardedSubmit((acknowledge) =>
    form
      .transform((data: Record<string, unknown>) => ({ ...data, acknowledge_access_change: acknowledge }))
      .patch(route('dutiables.update', props.dutiable.id), { preserveScroll: true, preserveState: true }),
  );

const onDelete = () =>
  guardedSubmit((acknowledge) =>
    router.delete(route('dutiables.destroy', props.dutiable.id), {
      data: { acknowledge_access_change: acknowledge },
      preserveScroll: true,
      preserveState: true,
    }),
  );
</script>
