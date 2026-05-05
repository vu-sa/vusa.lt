<template>
  <PageContent :title="`${duty.name.lt} (${duty.institution?.short_name ??
    duty.institution?.name ??
    'Neturi institucijos'
  })`" :back-url="route('duties.index')" :heading-icon="Icons.DUTY">
    <UpsertModelLayout>
      <DutyForm :duty :roles :duty-types :assignable-institutions :assignable-users
        @submit:form="(form) => form.patch(route('duties.update', duty.id), { preserveScroll: true })"
        @delete="() => router.delete(route('duties.destroy', duty.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

defineProps<{
  duty: App.Entities.Duty;
  roles: App.Entities.Role[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers: App.Entities.User[];
  dutyTypes: App.Entities.Type[];
}>();
</script>
