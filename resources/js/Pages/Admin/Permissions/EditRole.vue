<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <RoleForm :role="role" model-route="roles.update" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";

import RoleForm from "@/Components/AdminForms/RolePermissionForms.vue";

defineProps<{
  role: App.Entities.Role;
}>();
</script>
