<template>
  <PageContent title="Nauja institucija" :heading-icon="Icons.INSTITUTION">
    <UpsertModelLayout>
      <InstitutionForm :assignable-tenants :institution :institution-types
        @submit:form="(form) => form.post(route('institutions.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import Icons from "@/Types/Icons/regular";
import InstitutionForm from "@/Components/AdminForms/InstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
  institutionTypes: App.Entities.Type[];
}>();

const institution = {
  name: { lt: "", en: "" },
  short_name: { lt: "", en: "" },
  description: { lt: "", en: "" },
  address: { lt: "", en: "" },
  website: "",
  alias: "",
  image_url: null,
  logo_url: null,
  is_active: 1,
  tenant_id: null,
  types: null,
};
</script>
