<template>
  <TenantForm
    :tenant
    :assignable-institutions
    enable-delete
    @submit:form="submitForm"
    @delete="() => router.delete(route('tenants.destroy', tenant.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import TenantForm from '@/Components/AdminForms/TenantForm.vue';

const props = defineProps<{
  assignableInstitutions: Array<App.Entities.Institution>;
  tenant: App.Entities.Tenant;
}>();

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<Record<string, unknown>>;
  inertiaForm.defaults();
  inertiaForm.patch(route('tenants.update', props.tenant.id), { preserveScroll: true });
}
</script>
