<template>
  <CalendarForm
    :calendar
    remember-key="CreateCalendar"
    :event-types
    :available-tags
    :assignable-tenants
    :submit-url="route('calendar.store')"
    submit-method="post"
    @submit:form="handleCreateCalendar"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import { calendarTemplate as calendar } from '@/Types/formTemplates';
import CalendarForm from '@/Components/AdminForms/CalendarForm.vue';

defineProps<{
  eventTypes: App.Entities.EventType[];
  availableTags?: App.Entities.Tag[];
  assignableTenants: App.Entities.Tenant[];
}>();

function handleCreateCalendar(form: unknown) {
  (form as InertiaForm<CalendarEventForm>).post(route('calendar.store'), {
    forceFormData: true,
  });
}
</script>
