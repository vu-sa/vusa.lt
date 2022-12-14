<template>
  <PageContent title="Nauja institucija">
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyInstitution">
      <DutyInstitutionForm
        :padaliniai="padaliniai"
        model-route="dutyInstitutions.store"
        :duty-institution="dutyInstitution"
        :duty-institution-types="dutyInstitutionTypes"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { reactive } from "vue";

import DutyInstitutionForm from "@/Components/AdminForms/DutyInstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  padaliniai: Array<App.Models.Padalinys>;
  dutyInstitutionTypes: App.Models.DutyInstitutionType[];
}>();

const dutyInstitution = reactive({
  name: "",
  short_name: "",
  alias: "",
  description: "",
  padalinys_id: null,
  types: null,
  attributes: {
    en: {
      name: "",
      short_name: "",
      alias: "",
      description: "",
    },
  },
});
</script>
