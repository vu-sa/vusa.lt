<template>
  <PageContent :title="survey.name?.lt" :heading-icon="ClipboardList">
    <UpsertModelLayout>
      <SurveyForm
        :survey="survey"
        :assignable-tenants="assignableTenants"
        @submit:form="(form) => form.patch(route('surveys.update', survey.id), { preserveScroll: true })"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ClipboardList } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import SurveyForm from '@/Components/AdminForms/SurveyForm.vue';

defineProps<{
  survey: Record<string, any>;
  assignableTenants: App.Entities.Tenant[];
}>();
</script>
