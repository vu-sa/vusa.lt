<template>
  <PageContent title="Naujas kontaktas">
    <UpsertModelLayout :errors="$page.props.errors" :model="user">
      <UserForm
        :user="user"
        :duties="duties"
        :roles="roles"
        model-route="users.store"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineOptions({ layout: AdminLayout });

defineProps<{
  roles: App.Entities.Role[];
  duties: App.Entities.Duty[];
}>();

const user = {
  name: "",
  email: "",
  phone: null,
  duties: [],
  roles: [],
  profile_photo_path: null,
};
</script>
