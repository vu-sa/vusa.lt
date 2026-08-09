<template>
  <PageContent :title="template.title" :heading-icon="ListChecks">
    <UpsertModelLayout>
      <SurveyQuestionTemplateForm
        :template="template"
        :assignable-tenants="assignableTenants"
        :question-types="questionTypes"
        @submit:form="(form) => form.patch(route('surveyQuestionTemplates.update', template.id), { preserveScroll: true })"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ListChecks } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import SurveyQuestionTemplateForm from '@/Components/AdminForms/SurveyQuestionTemplateForm.vue';

defineProps<{
  template: Record<string, any>;
  assignableTenants: App.Entities.Tenant[];
  questionTypes: { value: string; label: string; hasOptions: boolean }[];
}>();
</script>
