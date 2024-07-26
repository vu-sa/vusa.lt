<template>
  <SummerCamps v-if="$page.props.app.locale === 'lt'" />

  <div v-if="news.length > 0" class="mx-auto mt-2">
    <NewsElement :news="news" />
  </div>

  <IndividualStudies />

  <EventCalendar :show-photos="true" :calendar="calendar" :upcoming-events="upcomingEvents" />
</template>

<script setup lang="ts">
import { defineAsyncComponent } from "vue";

defineProps<{
  news: Array<App.Entities.News>;
  calendar: Array<App.Entities.Calendar>;
  upcomingEvents: Array<App.Entities.Calendar>;
}>();

const EventCalendar = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/EventCalendarElement.vue"),
);

const NewsElement = defineAsyncComponent(
  () => import("@/Components/Public/NewsElement.vue"),
);

const SummerCamps = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/SummerCamps.vue"),
);

const IndividualStudies = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/IndividualStudies.vue"),
);
</script>
