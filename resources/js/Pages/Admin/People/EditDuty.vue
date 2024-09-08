<template>
  <PageContent
    :title="`${duty.name.lt} (${
      duty.institution?.short_name ??
      duty.institution?.name ??
      'Neturi institucijos'
    })`"
    :back-url="route('duties.index')"
    :heading-icon="Icons.DUTY"
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

import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import Icons from "@/Types/Icons/regular";
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
</script>
