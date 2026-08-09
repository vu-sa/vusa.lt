<template>
  <PageContent :title="$t('surveys.question_bank')" :heading-icon="ListChecks">
    <UpsertModelLayout>
      <SurveyQuestionTemplateForm
        :template="template"
        :assignable-tenants="assignableTenants"
        :question-types="questionTypes"
        @submit:form="(form) => form.post(route('surveyQuestionTemplates.store'))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ListChecks } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import SurveyQuestionTemplateForm from '@/Components/AdminForms/SurveyQuestionTemplateForm.vue';

defineProps<{
  assignableTenants: App.Entities.Tenant[];
  questionTypes: { value: string; label: string; hasOptions: boolean }[];
}>();

const template = {
  group_name: { lt: '', en: '' },
  question: { lt: '', en: '' },
  help: { lt: '', en: '' },
  options: [],
};
</script>
