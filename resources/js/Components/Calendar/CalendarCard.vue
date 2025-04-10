<template>
  <Card class="calendar-card border shadow-sm transition-all duration-300 hover:shadow-md dark:border-zinc-600/30">
    <div class="relative h-40 w-full overflow-hidden">
      <img v-if="calendarEvent.images && calendarEvent.images?.length > 0"
        class="h-full w-full rounded-t-md object-cover object-center transition-transform duration-500 hover:scale-105"
        :src="calendarEvent.images[0].original_url" :alt="calendarEvent.title">
      <div v-else
        class="flex h-full w-full items-center justify-center rounded-t-md bg-gradient-to-br from-red-50 to-red-100 dark:from-zinc-800 dark:to-zinc-700">
        <IFluentCalendarLtr24Regular class="text-4xl text-red-500 dark:text-red-400" />
      </div>

      <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-3 text-white">
        <div class="inline-flex items-center rounded-full bg-red-500/90 px-2 py-0.5 text-xs text-white">
          {{ formatDate(calendarEvent.date) }}
        </div>
      </div>
    </div>

    <CardHeader>
      <div class="p-1">
        <p class="line-clamp-2 h-12 w-full text-xl font-bold leading-tight">
          {{ calendarEvent.title }}
        </p>
      </div>
    </CardHeader>

    <CardContent class="mb-2 flex flex-col gap-2 text-sm">
      <div class="inline-flex items-center gap-2">
        <IFluentCalendarLtr20Regular class="text-red-500 dark:text-red-400" />
        <strong>
          {{ formatStaticTime(
            new Date(calendarEvent.date),
            {
              year: "numeric",
              month: "short",
              day: "numeric",
              hour: "numeric",
              minute: "numeric",
            },
            $page.props.app.locale
          ) }}
          <template v-if="calendarEvent.end_date">
            <span class="mx-1">-</span>
            {{ formatStaticTime(
              new Date(calendarEvent.end_date),
              isSameDay(new Date(calendarEvent.date), new Date(calendarEvent.end_date))
                ? { hour: "numeric", minute: "numeric" }
                : { year: "numeric", month: "short", day: "numeric", hour: "numeric", minute: "numeric" },
              $page.props.app.locale
            ) }}
          </template>
        </strong>
      </div>

      <div v-if="calendarEvent.location" class="inline-flex items-center gap-2">
        <IFluentLocation20Regular class="text-red-500 dark:text-red-400" />
        <a class="line-clamp-1 hover:text-red-500 hover:underline dark:hover:text-red-400" target="_blank"
          :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(calendarEvent.location)}`">
          {{ calendarEvent.location }}
        </a>
      </div>

      <div class="inline-flex items-center gap-2">
        <IFluentPeopleTeam20Regular class="text-red-500 dark:text-red-400" />
        <span class="line-clamp-1">
          {{ $t("Organizuoja") }}:
          <strong>{{ eventOrganizer }}</strong>
        </span>
      </div>
    </CardContent>

    <CardFooter v-if="!hideFooter" class="flex justify-between">
      <div v-if="googleLink || calendarEvent.facebook_url" class="flex gap-2">
        <NPopover v-if="googleLink" trigger="hover">
          {{ $t("Įsidėk į Google kalendorių") }}
          <template #trigger>
            <NButton secondary circle size="small" tag="a" target="_blank" :href="googleLink" @click.stop>
              <template #icon>
                <IMdiGoogle />
              </template>
            </NButton>
          </template>
        </NPopover>

        <NPopover v-if="calendarEvent.facebook_url" trigger="hover">
          {{ $t("Facebook renginys") }}
          <template #trigger>
            <NButton title="Facebook" secondary tag="a" target="_blank" :href="calendarEvent.facebook_url" circle
              size="small">
              <IMdiFacebook />
            </NButton>
          </template>
        </NPopover>
      </div>

      <div class="ml-auto">
        <NButton
          v-if="calendarEvent.url || (calendarEvent.tenant?.alias === 'mif' && calendarEvent.category?.alias === 'freshmen-camps')"
          strong tag="a" round type="primary" :href="calendarEvent.url" target="_blank"
          @click="calendarEvent.tenant?.alias === 'mif' && calendarEvent.category === 'freshmen-camps' ? showModal = true : null">
          <template #icon>
            <IFluentHatGraduation20Filled />
          </template>
          {{ $t("Dalyvauk") }}!
        </NButton>

        <NButton v-else secondary round tag="a"
          :href="route('calendar.event', { calendar: calendarEvent.id, lang: $page.props.app.locale })">
          {{ $t("Daugiau") }}
        </NButton>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { NButton, NPopover } from "naive-ui";
import { computed, ref } from "vue";

// Helper function to check if two dates are on the same day
const isSameDay = (date1: Date, date2: Date): boolean => {
  return date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate();
};

import Card from "../ShadcnVue/ui/card/Card.vue";
import CardContent from '../ShadcnVue/ui/card/CardContent.vue';
import CardFooter from '../ShadcnVue/ui/card/CardFooter.vue';
import CardHeader from '../ShadcnVue/ui/card/CardHeader.vue';

import { formatStaticTime } from "@/Utils/IntlTime";
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{
  calendarEvent: App.Entities.Calendar;
  googleLink?: string;
  hideFooter?: boolean;
}>();

const eventOrganizer = computed(() => {
  return (
    props.calendarEvent.organizer ??
    props.calendarEvent.tenant?.shortname
  );
});

const showModal = ref(false);

// Format date for the tag
const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat(usePage().props.app.locale, {
    month: "short",
    day: "numeric",
  }).format(date);
};
</script>

<style scoped>
.calendar-card {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.calendar-card :deep(.vc-container) {
  border: none;
  font-family: 'Inter', sans-serif;
}
</style>
