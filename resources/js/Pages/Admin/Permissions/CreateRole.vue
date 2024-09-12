<template>
  <PageContent title="Nauja rolė" :back-url="route('roles.index')">
    <UpsertModelLayout>
      <RoleForm :role @submit:form="(form) => form.post(route('roles.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import RoleForm from "@/Components/AdminForms/RoleForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const role = {
  name: "",
};
</script>
