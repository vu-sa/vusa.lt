<template>
  <InstitutionForm
    remember-key="CreateInstitution"
    :assignable-tenants
    :institution
    :institution-types
    @submit:form="handleSubmit"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import InstitutionForm from '@/Components/AdminForms/InstitutionForm.vue';

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
  institutionTypes: App.Entities.Type[];
}>();

const institution = {
  name: { lt: '', en: '' },
  short_name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  address: { lt: '', en: '' },
  working_hours: { lt: '', en: '' },
  website: '',
  alias: '',
  image_url: null,
  image_focal_point: null,
  logo_url: null,
  is_active: true,
  tenant_id: null,
  types: [],
} as unknown as App.Entities.Institution;

const handleSubmit = (form: unknown) => {
  (form as InertiaForm<Record<string, unknown>>).post(route('institutions.store'), {
    onSuccess: () => {
      router.visit(route('institutions.index'));
    },
  });
};
</script>
