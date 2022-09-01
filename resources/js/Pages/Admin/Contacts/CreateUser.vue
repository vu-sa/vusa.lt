<template>
  <PageContent :title="contact.name">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm :user="contact" model-route="users.store" :roles="roles" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/Components/Admin/Forms/UserForm.vue";

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
