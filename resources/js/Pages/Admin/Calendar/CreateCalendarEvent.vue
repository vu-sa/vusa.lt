<template>
  <PageContent title="Naujas įvykis">
    <UpsertModelLayout>
      <CalendarForm :calendar :categories :assignable-tenants
        @submit:form="(form) => form.post(route('calendar.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { calendarTemplate as calendar } from "@/Types/formTemplates";
import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
}>();
</script>
