<template>
  <Card class="calendar-card border shadow-sm transition-all duration-300 hover:shadow-md dark:border-zinc-600/30">
    <div class="relative h-40 w-full overflow-hidden">
      <img v-if="calendarEvent.main_image_url"
        class="h-full w-full rounded-t-md object-cover object-center transition-transform duration-500 hover:scale-105"
        :src="calendarEvent.main_image_url" :alt="calendarEvent.title">
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
      <CalendarEventMeta
        :date="calendarEvent.date"
        :end-date="calendarEvent.end_date"
        :location="calendarEvent.location"
        :organizer="eventOrganizer"
        :tenant="calendarEvent.tenant"
        variant="neutral"
        :enable-location-link="true"
      />
    </CardContent>

    <CardFooter v-if="!hideFooter" class="flex justify-between">
      <div v-if="googleLink || calendarEvent.facebook_url" class="flex gap-2">
        <!-- Google Calendar Button -->
        <Button
          v-if="googleLink"
          variant="outline"
          size="icon"
          as="a"
          :href="googleLink"
          target="_blank"
          :title="$t('Įsidėk į Google kalendorių')"
          @click.stop
        >
          <IMdiGoogle class="h-4 w-4" />
        </Button>

        <!-- Facebook Event Button -->
        <Button
          v-if="calendarEvent.facebook_url"
          variant="outline"
          size="icon"
          as="a"
          :href="calendarEvent.facebook_url"
          target="_blank"
          :title="$t('Facebook renginys')"
        >
          <IMdiFacebook class="h-4 w-4" />
        </Button>
      </div>

      <div class="ml-auto">
        <!-- Primary Action Button -->
        <Button
          v-if="calendarEvent.url || (calendarEvent.tenant?.alias === 'mif' && calendarEvent.category?.alias === 'freshmen-camps')"
          as="a"
          :href="calendarEvent.url"
          target="_blank"
          class="gap-2"
          @click="calendarEvent.tenant?.alias === 'mif' && calendarEvent.category === 'freshmen-camps' ? showModal = true : null"
        >
          <IFluentHatGraduation20Filled class="h-4 w-4" />
          {{ $t("Dalyvauk") }}!
        </Button>

        <!-- View Details Button -->
        <Button
          v-else
          variant="outline"
          as="a"
          :href="route('calendar.event', { calendar: calendarEvent.id, lang: $page.props.app.locale })"
        >
          {{ $t("Daugiau") }}
        </Button>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="tsx">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import Card from '../ui/card/Card.vue';
import CardContent from '../ui/card/CardContent.vue';
import CardFooter from '../ui/card/CardFooter.vue';
import CardHeader from '../ui/card/CardHeader.vue';
import Button from '../ui/button/Button.vue';

import CalendarEventMeta from './CalendarEventMeta.vue';

const props = defineProps<{
  calendarEvent: App.Entities.Calendar;
  googleLink?: string;
  hideFooter?: boolean;
}>();

const eventOrganizer = computed(() => {
  return (
    props.calendarEvent.organizer
    ?? props.calendarEvent.tenant?.shortname
  );
});

const showModal = ref(false);

// Format date for the tag
const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat(usePage().props.app.locale, {
    month: 'short',
    day: 'numeric',
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
