<template>
  <PageContent title="Nauja pareiga" :heading-icon="Icons.DUTY">
    <UpsertModelLayout>
      <DutyForm remember-key="CreateDuty" :duty :duty-types :assignable-institutions :assignable-users :roles
        @submit:form="(form) => form.post(route('duties.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  dutyTypes: App.Entities.Type[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers: App.Entities.User[];
  roles: App.Entities.Role[];
}>();

const duty = {
  name: { lt: "", en: "" },
  description: { lt: "", en: "" },
  email: null,
  institution_id: null,
  places_to_occupy: null,
  types: [],
  roles: [],
  current_users: [],
};
</script>
