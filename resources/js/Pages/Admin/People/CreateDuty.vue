<template>
  <DutyForm
    :duty
    :duty-types
    :assignable-institutions
    :roles
    :assignable-tenants
    :assignable-duties
    @submit:form="handleSubmit"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import { DutyIconFilled } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  dutyTypes: App.Entities.Type[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers?: App.Entities.User[];
  roles: App.Entities.Role[];
  assignableTenants: { id: number; shortname: string; type?: string }[];
  assignableDuties: Array<{ id: string; name: string; institution?: App.Entities.Institution | Record<string, unknown> }>;
  prefillInstitutionId?: string | null;
}>();

usePageBreadcrumbs(BreadcrumbHelpers.adminForm('Pareigybės', 'duties.index', 'Nauja pareigybė', DutyIconFilled));

const duty = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  email: null,
  institution_id: props.prefillInstitutionId ?? null,
  places_to_occupy: 1,
  contacts_grouping: 'none',
  types: [],
  roles: [],
  ex_officio_target_duties: [],
  assignable_tenants: [],
};

const handleSubmit = (form: InertiaForm<Record<string, unknown>>) => {
  form.post(route('duties.store'), {
    onSuccess: () => {
      router.visit(route('duties.index'));
    },
  });
};
</script>
