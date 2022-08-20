<template>
  <PageContent title="Nauja institucija">
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyInstitution">
      <DutyInstitutionForm
        :padaliniai="padaliniai"
        model-route="dutyInstitutions.store"
        :duty-institution="dutyInstitution"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { reactive } from "vue";

import DutyInstitutionForm from "@/Components/Admin/Forms/DutyInstitutionForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
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
