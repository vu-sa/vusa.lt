<template>
  <PageContent title="Nauja pareiga">
    <UpsertModelLayout :errors="$page.props.errors" :model="duty">
      <DutyForm
        :duty="duty"
        :duty-types="dutyTypes"
        :institutions="assignableInstitutions"
        :assignable-users="assignableUsers"
        :roles="roles"
        model-route="duties.store"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  dutyTypes: App.Entities.Type[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers: App.Entities.User[];
  roles: App.Entities.Role[];
}>();

const duty = {
  name: "",
  description: "",
  email: null,
  institution: {},
  places_to_occupy: null,
  types: [],
  roles: [],
  users: [],
  extra_attributes: {
    en: {
      name: "",
      description: "",
    },
  },
};
</script>
