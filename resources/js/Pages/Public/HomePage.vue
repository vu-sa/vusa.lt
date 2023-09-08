<template>
  <Head>
    <title>Pagrindinis</title>
  </Head>

  <div v-if="news.length > 0" class="mx-auto mt-2">
    <NewsElement :news="news" />
  </div>

  <IndividualStudies />

  <EventCalendar
    :show-photos="true"
    :calendar="calendar"
    :upcoming4-events="upcoming4Events"
  />

  <SummerCamps v-if="$page.props.app.locale === 'lt'" />

  <YearReport2022 />
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { defineAsyncComponent } from "vue";

defineProps<{
  news: Array<App.Entities.News>;
  calendar: Array<App.Entities.Calendar>;
  upcoming4Events: Array<App.Entities.Calendar>;
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

// yearreport2022 make unsuspensible
const YearReport2022 = defineAsyncComponent(
  // eslint-disable-next-line no-secrets/no-secrets
  () => import("@/Components/Public/FullWidth/YearReport2022.vue"),
);
</script>
