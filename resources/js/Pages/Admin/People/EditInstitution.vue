<template>
  <InstitutionForm
    enable-delete
    :assignable-tenants
    :institution
    :institution-types
    @submit:form="(form) => form.patch(route('institutions.update', institution.id), { preserveScroll: true })"
    @delete="() => router.delete(route('institutions.destroy', institution.id))"
  />
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import InstitutionForm from '@/Components/AdminForms/InstitutionForm.vue';

defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: Array<App.Entities.Type>;
  assignableTenants: Array<App.Entities.Tenant>;
}>();
</script>
