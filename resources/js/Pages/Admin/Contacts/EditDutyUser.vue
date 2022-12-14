<template>
  <PageContent
    :title="`${dutyUser.duty.name} (${dutyUser.user.name})`"
    :back-url="route('users.edit', dutyUser.user.id)"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyUser">
      <DutyUserForm :duty-user="dutyUser" model-route="dutyUsers.update" />
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
import route from "ziggy-js";

import DutyUserForm from "@/Components/AdminForms/DutyUserForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  dutyUser: Record<string, any>;
}>();
</script>
