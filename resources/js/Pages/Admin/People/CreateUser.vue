<template>
  <PageContent title="Naujas studentas" :heading-icon="Icons.USER">
    <UpsertModelLayout :errors="$page.props.errors" :model="user">
      <UserForm :user :roles :padaliniai-with-duties :permissable-padaliniai model-route="users.store" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineProps<{
  roles: App.Entities.Role[];
  padaliniaiWithDuties: App.Entities.Padalinys[];
  permissablePadaliniai: App.Entities.Padalinys[];
}>();

const user = {
  name: "",
  email: "",
  phone: null,
  current_duties: [],
  roles: [],
  profile_photo_path: null,
  pronouns: { lt: "", en: "" },
  show_pronouns: false,
};
</script>
