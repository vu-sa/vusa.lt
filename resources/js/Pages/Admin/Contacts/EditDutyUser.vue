<template>
  <AdminLayout
    :title="`${dutyUser.duty.name} (${dutyUser.user.name})`"
    :back-url="route('users.edit', dutyUser.user.id)"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyUser">
      <DutyUserForm :duty-user="dutyUser" model-route="dutyUsers.update" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import DutyUserForm from "@/Components/Admin/Forms/DutyUserForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  dutyUser: Record<string, any>;
}>();

// const { message } = createDiscreteApi(["message"]);
</script>
