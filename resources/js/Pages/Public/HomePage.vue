<template>
  <!-- <SummerCamps v-if="$page.props.app.locale === 'lt'" /> -->
  <template v-if="$page.props.auth?.user">
    <h2 class="text-4xl font-bold tracking-tight">
      {{ $t('Labas') }}, {{ userNameAddress }}! 👋
    </h2>
    <AdminMultiHomeCards />
  </template>
  <div v-if="news.length > 0" class="mx-auto mt-8">
    <NewsElement :news="news" />
  </div>

  <EventCalendar :show-photos="true" :calendar :upcoming-events />
</template>

<script setup lang="ts">
import { addressivize } from "@/Utils/String";
import { usePage } from "@inertiajs/vue3";
import { computed, defineAsyncComponent } from "vue";

defineProps<{
  news: Array<App.Entities.News>;
  calendar: Array<App.Entities.Calendar>;
  upcomingEvents: Array<App.Entities.Calendar>;
}>();

const EventCalendar = defineAsyncComponent(
  () => import("@/Components/Public/FullWidth/EventCalendarElement.vue"),
);

const NewsElement = defineAsyncComponent(
  () => import("@/Components/Public/NewsElement.vue"),
);

const AdminMultiHomeCards = defineAsyncComponent(
  () => import("@/Components/Cards/AdminMultiHomeCards.vue"),
);

const userNameAddress = computed(() => {
  const name = usePage().props.auth?.user.name;

  // Split
  const split = name?.split(" ");

  if (!split) {
    return "";
  }

  const firstName = split[0];

  return usePage().props.app.locale === 'lt' ? addressivize(firstName) : firstName;
});

//const SummerCamps = defineAsyncComponent(
//  // eslint-disable-next-line no-secrets/no-secrets
//  () => import("@/Components/Public/FullWidth/SummerCamps.vue"),
//);
</script>
