<template>
  <DutyForm
    :duty
    :duty-types
    :assignable-institutions
    :assignable-tenants
    :assignable-duties
    @submit:form="handleSubmit"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';

const props = defineProps<{
  dutyTypes: App.Entities.DutyType[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers?: App.Entities.User[];
  assignableTenants: { id: number; shortname: string; type?: string }[];
  assignableDuties: Array<{ id: string; name: string; institution?: App.Entities.Institution | Record<string, unknown> }>;
  prefillInstitutionId?: string | null;
}>();

const duty = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  email: null,
  institution_id: props.prefillInstitutionId ?? null,
  places_to_occupy: null,
  contacts_grouping: 'none',
  types: [],
  ex_officio_target_duties: [],
  assignable_tenants: [],
};

const handleSubmit = (form: InertiaForm<Record<string, unknown>>) => {
  form.post(route('duties.store'));
};
</script>
