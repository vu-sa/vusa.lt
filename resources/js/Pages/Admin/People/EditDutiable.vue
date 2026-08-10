<template>
  <PageContent :title="pageTitle"
    :back-url="route('users.edit', dutiable.dutiable?.id || '')" :heading-icon="DutyIcon">
    <UpsertModelLayout>
      <DutiableForm :dutiable :study-programs
        @submit:form="onSubmit"
        @delete="onDelete" />
    </UpsertModelLayout>
    <AccessChangeWarningDialog :open :report
      @update:open="open = $event" @confirm="confirm" @cancel="cancel" />
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

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

const pageTitle = computed(() => {
  const dutyName = props.dutiable.duty?.name || $t('forms.dutiable_title_fallback_duty');
  const personName = props.dutiable.dutiable?.name || $t('forms.dutiable_title_fallback_person');
  return `${dutyName} (${personName})`;
});

const onSubmit = (form: any) =>
  guardedSubmit(acknowledge =>
    form
      .transform((data: Record<string, unknown>) => ({ ...data, acknowledge_access_change: acknowledge }))
      .patch(route('dutiables.update', props.dutiable.id), { preserveScroll: true, preserveState: true }),
  );

const onDelete = () =>
  guardedSubmit(acknowledge =>
    router.delete(route('dutiables.destroy', props.dutiable.id), {
      data: { acknowledge_access_change: acknowledge },
      preserveScroll: true,
      preserveState: true,
    }),
  );
</script>
