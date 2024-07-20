<template>
  <PageContent title="Naujas padalinys" :heading-icon="Icons.TENANT">
    <UpsertModelLayout :errors="$page.props.errors" :model="tenant">
      <TenantForm model-route="tenants.update" :tenant :assignable-institutions />
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
