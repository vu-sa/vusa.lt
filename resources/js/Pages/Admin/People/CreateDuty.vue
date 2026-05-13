<template>
  <PageContent title="Nauja pareiga" :heading-icon="Icons.DUTY">
    <UpsertModelLayout>
      <DutyForm remember-key="CreateDuty" :duty :duty-types :assignable-institutions :assignable-users :roles
        :assignable-tenants :assignable-duties @submit:form="handleSubmit" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { router } from '@inertiajs/vue3';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

const props = defineProps<{
  dutyTypes: App.Entities.Type[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers: App.Entities.User[];
  roles: App.Entities.Role[];
  assignableTenants: { id: number; shortname: string; type?: string }[];
  assignableDuties: Array<{ id: string; name: string; institution?: { id: string; name: string; short_name?: string | null; tenant?: { id: number; shortname: string } | null } | null }>;
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
  roles: [],
  current_users: [],
  ex_officio_target_duties: [],
  assignable_tenants: [],
};

const handleSubmit = (form: any) => {
  form.post(route('duties.store'), {
    onSuccess: () => {
      router.visit(route('duties.index'));
    },
  });
};
</script>
