<template>
  <PageContent title="Naujas padalinys" :heading-icon="TenantIcon">
    <UpsertModelLayout>
      <TenantForm remember-key="CreateTenant" :tenant :assignable-institutions @submit:form="(form) => form.post(route('tenants.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import TenantForm from '@/Components/AdminForms/TenantForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { TenantIcon } from '@/Components/icons';

defineProps<{
  assignableInstitutions: Array<App.Entities.Institution>;
}>();

const tenant = ref({
  fullname: '',
  shortname: '',
  type: '',
  alias: '',
  shortname_vu: '',
  primary_institution_id: null,
});
</script>
