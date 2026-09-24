<template>
  <TenantForm
    remember-key="CreateTenant"
    :tenant
    :assignable-institutions
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import TenantForm from '@/Components/AdminForms/TenantForm.vue';
import { TenantIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

defineProps<{
  assignableInstitutions: Array<App.Entities.Institution>;
}>();

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Padaliniai', 'tenants.index', 'Naujas padalinys', TenantIcon),
);

const tenant = ref({
  fullname: '',
  shortname: '',
  type: 'padalinys',
  alias: '',
  shortname_vu: '',
  primary_institution_id: null,
} as unknown as App.Entities.Tenant);

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('tenants.store'));
}
</script>
