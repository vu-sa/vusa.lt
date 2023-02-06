<template>
  <PageContent
    :title="`${duty.name} (${
      duty.institution?.short_name ?? 'Neturi institucijos'
    })`"
    :back-url="route('duties.index')"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="duty">
      <DutyForm
        :duty="duty"
        :roles="roles"
        :duty-types="dutyTypes"
        :institutions="assignableInstitutions"
        :assignable-users="assignableUsers"
        model-route="duties.update"
        delete-model-route="duties.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import { checkForEmptyArray } from "@/Composables/checkAttributes";

import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  duty: App.Entities.Duty;
  roles: App.Entities.Role[];
  assignableInstitutions: App.Entities.Institution[];
  assignableUsers: App.Entities.User[];
  dutyTypes: App.Entities.Type[];
}>();

const duty = ref(props.duty);

duty.value.extra_attributes = checkForEmptyArray(duty.value.extra_attributes);
duty.value.extra_attributes.en = checkForEmptyArray(
  duty.value.extra_attributes.en
);

////////////////////////////////////////////////////////////////////////////////
</script>
