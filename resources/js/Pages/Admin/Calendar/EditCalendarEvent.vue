<template>
  <PageContent :title="calendar.title.lt" :back-url="route('calendar.index')" :heading-icon="Icons.CALENDAR">
    <UpsertModelLayout>
      <CalendarForm model-route="calendar.update" delete-model-route="calendar.destroy" :calendar :categories :images
        :assignable-tenants />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  calendar: App.Entities.Calendar;
  categories: App.Entities.Category[];
  images: any;
  assignableTenants: App.Entities.Tenant[];
}>();

const calendar = ref(props.calendar);
</script>
