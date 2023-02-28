<template>
  <PageContent
    :title="user.name"
    :back-url="route('users.index')"
    :heading-icon="Icons.USER"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="user">
      <UserForm
        :user="user"
        :roles="roles"
        :padaliniai-with-duties="padaliniaiWithDuties"
        model-route="users.update"
        delete-model-route="users.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  // TODO: don't return all duties from the controller immedixxately
  padaliniaiWithDuties: App.Entities.Padalinys[];
}>();
</script>
