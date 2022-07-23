<template>
  <AdminLayout :title="contact.name">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm :user="contact" model-route="users.store" :roles="roles" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/components/Admin/Forms/UserForm.vue";

defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();

const contact = {
  name: "",
  email: "",
  phone: null,
  role: {
    id: null,
  },
  duties: [],
  profile_photo_path: null,
};
</script>
