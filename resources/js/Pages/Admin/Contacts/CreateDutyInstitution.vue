<template>
  <AdminLayout title="Nauja institucija">
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyInstitution">
      <DutyInstitutionForm
        :padaliniai="padaliniai"
        model-route="dutyInstitutions.store"
        :duty-institution="dutyInstitution"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import { reactive } from "vue";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import DutyInstitutionForm from "@/Components/Admin/Forms/DutyInstitutionForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  padaliniai: Array<App.Models.Padalinys>;
}>();

const dutyInstitution = reactive({
  name: "",
  short_name: "",
  alias: "",
  description: "",
  padalinys_id: null,
});
</script>
