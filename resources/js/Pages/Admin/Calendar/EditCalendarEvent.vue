<template>
  <PageContent :title="calendar.title">
    <UpsertModelLayout :errors="$page.props.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.update"
        delete-model-route="calendar.destroy"
        :calendar="calendar"
        :categories="categories"
        :images="images"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  calendar: App.Entities.Calendar;
  images: any;
  categories: App.Entities.Category[];
}>();

const calendar = ref(props.calendar);

calendar.value.extra_attributes = checkForEmptyArray(
  calendar.value.extra_attributes
);
calendar.value.extra_attributes.en = checkForEmptyArray(
  calendar.value.extra_attributes.en
);
</script>
