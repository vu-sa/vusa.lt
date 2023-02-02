<template>
  <PageContent
    :title="`${dutiable.duty.name} (${dutiable.user.name})`"
    :back-url="route('users.edit', dutiable.user.id)"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="dutiable">
      <DutiableForm :dutiable="dutiable" model-route="duties.users.update" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import DutiableForm from "@/Components/AdminForms/DutiableForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  dutiable: App.Entities.Dutiable;
}>();
</script>
