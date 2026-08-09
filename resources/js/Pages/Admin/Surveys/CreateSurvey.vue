<template>
  <PageContent :title="$t('surveys.actions.create')" :heading-icon="ClipboardList">
    <UpsertModelLayout>
      <SurveyForm
        :survey="survey"
        :assignable-tenants="assignableTenants"
        @submit:form="(form) => form.post(route('surveys.store'))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ClipboardList } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import SurveyForm from '@/Components/AdminForms/SurveyForm.vue';

defineProps<{
  assignableTenants: App.Entities.Tenant[];
}>();

const survey = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  welcome_text: { lt: '', en: '' },
  tenant_id: null,
  starts_at: null,
  ends_at: null,
  is_anonymous: true,
};
</script>
