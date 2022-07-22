<template>
  <AdminLayout :title="contact.name">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm :user="contact" model-route="users.store" :roles="roles" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import { reactive } from "vue";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/components/Admin/Forms/UserForm.vue";

const props = defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();

const contact = reactive({
  name: "",
  email: "",
  phone: null,
  role: {
    id: null,
  },
  duties: [],
  profile_photo_path: null,
});
</script>
