<template>
  <PageContent :title="userName" :back-url="route('users.index')" :heading-icon="Icons.USER">
    <UpsertModelLayout :errors="$page.props.errors" :model="user">
      <UserForm :user :roles :padaliniai-with-duties :permissable-padaliniai model-route="users.update"
        delete-model-route="users.destroy" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";

import { usePage } from "@inertiajs/vue3";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  // TODO: don't return all duties from the controller immedixxately
  padaliniaiWithDuties: App.Entities.Padalinys[];
  permissablePadaliniai: App.Entities.Padalinys[];
}>();

const userName = computed(() => {
  if (props.user.show_pronouns) {
    return `${props.user.name} (${props.user.pronouns[usePage().props.app.locale]})`;
  } else {
    return props.user.name;
  }
});
</script>
