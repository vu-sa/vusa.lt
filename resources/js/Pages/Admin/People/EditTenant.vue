<template>
  <PageContent title="Naujas padalinys" :heading-icon="Icons.TENANT">
    <UpsertModelLayout>
      <TenantForm :tenant :assignable-institutions
        @submit:form="(form) => form.patch(route('tenants.update', tenant.id), { preserveScroll: true })" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TenantForm from "@/Components/AdminForms/TenantForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  assignableInstitutions: Array<App.Entities.Institution>;
  tenant: App.Entities.Tenant;
}>();
</script>
