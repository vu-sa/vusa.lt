<template>
  <PageContent title="Naujas studentas">
    <UpsertModelLayout :errors="$page.props.errors" :model="user">
      <UserForm
        :user="user"
        :roles="roles"
        :padaliniai-with-duties="padaliniaiWithDuties"
        model-route="users.store"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineProps<{
  roles: App.Entities.Role[];
  padaliniaiWithDuties: App.Entities.Padalinys[];
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
