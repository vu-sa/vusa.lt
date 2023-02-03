<template>
  <PageContent title="Nauja rolė" :back-url="route('roles.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="role">
      <RoleForm :role="role" model-route="roles.store" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import RoleForm from "@/Components/AdminForms/RoleForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const role = {
  name: "",
};
</script>
