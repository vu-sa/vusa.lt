<template>
  <AdminLayout :title="calendar.title">
    <UpsertModelLayout :errors="$attrs.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.update"
        delete-model-route="calendar.destroy"
        :calendar="calendar"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import { reactive } from "vue";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import CalendarForm from "@/components/Admin/Forms/CalendarForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  calendar: App.Models.Calendar;
}>();

const calendar = reactive(props.calendar);
</script>
