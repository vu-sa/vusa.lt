<template>
  <PageContent title="Nauja narystė" :heading-icon="UserIcon">
    <UpsertModelLayout>
      <MembershipForm remember-key="CreateMembership" :membership :assignable-tenants @submit:form="(form) => form.post(route('memberships.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import MembershipForm from '@/Components/AdminForms/MembershipForm.vue';
import { UserIcon } from '@/Components/icons';

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
}>();

const membership = ref({
  name: { lt: '', en: '' },
  tenant_id: null,
});
</script>
