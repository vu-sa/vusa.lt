<template>
  <PageContent
    :title="`${dutiable.duty.name} (${dutiable.user.name})`"
    :back-url="route('users.edit', dutiable.user.id)"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="dutiable">
      <DutiableForm :dutiable="dutiable" model-route="dutiables.update" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DutiableForm from "@/Components/AdminForms/DutiableForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  dutiable: App.Models.Dutiable;
}>();
</script>
