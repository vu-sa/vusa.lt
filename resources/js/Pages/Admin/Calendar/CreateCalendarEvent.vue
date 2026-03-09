<template>
  <PageContent title="Naujas įvykis">
    <UpsertModelLayout>
      <CalendarForm 
        :calendar 
        remember-key="CreateCalendar" 
        :categories 
        :assignable-tenants
        :submit-url="route('calendar.store')"
        submit-method="post"
        @submit:form="handleCreateCalendar" 
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import type { InertiaForm } from "@inertiajs/vue3";

import { calendarTemplate as calendar } from "@/Types/formTemplates";
import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
}>();

function handleCreateCalendar(form: InertiaForm<CalendarEventForm>) {
  form.post(route('calendar.store'), {
    forceFormData: true,
  });
}
</script>
