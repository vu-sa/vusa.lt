<template>
  <AdminLayout :title="contact.name">
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
import { reactive } from "vue";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/components/Admin/Forms/UserForm.vue";

const props = defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();

const contact = reactive(props.contact);
</script>
