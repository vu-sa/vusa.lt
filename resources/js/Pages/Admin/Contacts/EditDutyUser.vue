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
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import route from "ziggy-js";

import DutyUserForm from "@/Components/Admin/Forms/DutyUserForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  dutyUser: Record<string, any>;
}>();

// const { message } = createDiscreteApi(["message"]);
</script>
