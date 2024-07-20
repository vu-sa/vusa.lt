<template>
  <PageContent title="Nauja institucija" :heading-icon="Icons.INSTITUTION">
    <UpsertModelLayout :errors="$page.props.errors" :model="institution">
      <InstitutionForm :assignable-tenants model-route="institutions.store" :institution :institution-types />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { reactive } from "vue";

import Icons from "@/Types/Icons/regular";
import InstitutionForm from "@/Components/AdminForms/InstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
  institutionTypes: App.Entities.Type[];
}>();

const institution = reactive({
  name: "",
  short_name: "",
  alias: "",
  description: "",
  tenant_id: null,
  types: null,
  extra_attributes: {
    en: {
      name: "",
      short_name: "",
      alias: "",
      description: "",
    },
  },
});
</script>
