<template>
  <PageContent :title="$page.props.seo.title" :heading-icon="Icons.INSTITUTION">
    <UpsertModelLayout>
      <InstitutionForm remember-key="CreateInstitution" :assignable-tenants :institution :institution-types
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
  is_active: true,
  contacts_layout: "aside",
  tenant_id: null,
  types: null,
} as any;
</script>
