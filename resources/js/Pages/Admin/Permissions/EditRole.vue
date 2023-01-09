<template>
  <AdminContentPage :title="role.name" :back-url="route('roles.index')">
    <RoleForm :role="role" model-route="roles.update" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import RoleForm from "@/Components/AdminForms/RolePermissionForms.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  role: App.Entities.Role;
}>();
</script>
