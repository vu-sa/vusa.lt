<template>
  <AdminLayout :title="contact.name" :back-url="route('users.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm
        :user="contact"
        model-route="users.update"
        delete-model-route="users.destroy"
        :roles="roles"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/components/Admin/Forms/UserForm.vue";
import route from "ziggy-js";

defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();
</script>
