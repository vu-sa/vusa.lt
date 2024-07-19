<template>
  <PageContent
    :title="calendar.title"
    :back-url="route('calendar.index')"
    :heading-icon="Icons.CALENDAR"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.update"
        delete-model-route="calendar.destroy"
        :calendar="calendar"
        :categories="categories"
        :images="images"
        :padaliniai="padaliniai"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  calendar: App.Entities.Calendar;
  categories: App.Entities.Category[];
  images: any;
  padaliniai: App.Entities.Padalinys[];
}>();

const calendar = ref(props.calendar);

calendar.value.extra_attributes = checkForEmptyArray(
  calendar.value.extra_attributes
);
calendar.value.extra_attributes.en = checkForEmptyArray(
  calendar.value.extra_attributes.en
);

calendar.value.date = new Date(calendar.value.date).getTime();
calendar.value.end_date = new Date(calendar.value.end_date).getTime();
</script>
