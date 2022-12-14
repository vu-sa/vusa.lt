<template>
  <PageContent title="Naujas kontaktas">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm
        :user="contact"
        model-route="users.store"
        :roles="roles"
        :duties="duties"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineProps<{
  roles: App.Models.Role[];
  duties: App.Models.Duty[];
}>();

const contact = {
  name: "",
  email: "",
  phone: null,
  duties: [],
  roles: [],
  profile_photo_path: null,
};
</script>
