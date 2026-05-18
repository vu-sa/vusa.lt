<template>
  <PageContent title="Nauji mokymai" :heading-icon="UserIcon">
    <UpsertModelLayout>
      <TrainingCreateForm :training @submit:form="(form) => form.post(route('trainings.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import TrainingCreateForm from '@/Components/AdminForms/TrainingCreateForm.vue';
import { UserIcon } from '@/Components/icons';

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
}>();

const training = ref({
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  institution_id: null,
  start_time: null,
});
</script>
