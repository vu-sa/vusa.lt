<template>
  <PageContent :title="calendar.title">
    <UpsertModelLayout :errors="$attrs.errors" :model="calendar">
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

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { ref } from "vue";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import CalendarForm from "@/Components/Admin/Forms/CalendarForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  calendar: App.Models.Calendar;
  images: any;
  categories: App.Models.Category[];
}>();

const calendar = ref(props.calendar);

calendar.value.attributes = checkForEmptyArray(calendar.value.attributes);
calendar.value.attributes.en = checkForEmptyArray(calendar.value.attributes.en);
</script>
