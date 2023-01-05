<template>
  <PageContent title="Nauja institucija">
    <UpsertModelLayout :errors="$page.props.errors" :model="institution">
      <InstitutionForm
        :padaliniai="padaliniai"
        model-route="institutions.store"
        :institution="institution"
        :institution-types="institutionTypes"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { reactive } from "vue";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import InstitutionForm from "@/Components/AdminForms/InstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  padaliniai: Array<App.Models.Padalinys>;
  institutionTypes: App.Models.Type[];
}>();

const institution = reactive({
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
