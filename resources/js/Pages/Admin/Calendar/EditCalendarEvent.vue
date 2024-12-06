<template>
  <PageContent :title="calendar.title.lt" :back-url="route('calendar.index')" :heading-icon="Icons.CALENDAR">
    <UpsertModelLayout>
      <CalendarForm enable-delete :calendar :categories :assignable-tenants @submit:form="handleUpdateCalendar"
        @delete="() => router.delete(route('calendar.destroy', calendar.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router, type InertiaForm } from "@inertiajs/vue3";

import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  calendar: App.Entities.Calendar;
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
}>();

const calendar = ref(props.calendar);

function handleUpdateCalendar(form: InertiaForm<CalendarEventForm>) {
  form.transform((data) => ({
    ...data,
    _method: "patch"
  })).post(route('calendar.update', calendar.value.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
