<template>
  <div>
    <!-- Events counter with improved readability -->
    <div class="my-4 text-zinc-500 dark:text-zinc-400">
      {{ $t("Rasta :count renginių", { count: events.total.toString() }) }}
      <template v-if="events.last_page > 1">
        | {{ $t("Psl.") }} {{ events.current_page }} / {{ events.last_page }}
      </template>
    </div>

    <!-- List view of events -->
    <div class="mb-8">
      <div v-if="events.data.length > 0" class="space-y-3">
        <EventCard v-for="event in events.data" :key="event.id" :event
          :variant="tab === 'upcoming' ? 'upcoming' : 'past'" :google-link="generateGoogleLink(event)" />
      </div>
      <div v-else
        class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
        <p class="text-zinc-600 dark:text-zinc-400">
          {{ tab === 'upcoming' ? $t("Nėra būsimų renginių") : $t("Nėra praėjusių renginių") }}
        </p>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
      <NPagination v-if="events.total > events.per_page" :page-count="events.last_page" :page="events.current_page"
        :page-slot="7" @update:page="onPageChange" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NPagination } from "naive-ui";
import { Link, usePage } from "@inertiajs/vue3";

import EventCard from "./EventCard.vue";

import Button from "@/Components/ui/button/Button.vue";

const props = defineProps<{
  events: {
    data: App.Entities.Calendar[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    path: string;
    links: any[];
  };
  tab: string;
}>();

const emit = defineEmits(['pageChange']);

// Handle page change
const onPageChange = (page: number) => {
  emit('pageChange', page);
};

// Generate Google Calendar link for events
const generateGoogleLink = (event: App.Entities.Calendar) => {
  if (!event) return '';

  const startDate = new Date(event.date);
  const endDate = event.end_date ? new Date(event.end_date) : new Date(startDate.getTime() + 60 * 60 * 1000); // Default 1 hour

  const formatGoogleDate = (date: Date) => {
    return date.toISOString().replace(/-|:|\.\d+/g, '');
  };

  const { description } = event;
  const details = Array.isArray(description)
    ? description.join(' ').replace(/<[^>]*>/g, ' ')
    : (description || '').replace(/<[^>]*>/g, ' ');

  const title = Array.isArray(event.title) ? event.title.join(' ') : (event.title || '');
  const location = Array.isArray(event.location) ? event.location.join(' ') : (event.location || '');

  return `https://www.google.com/calendar/render?action=TEMPLATE` +
    `&text=${encodeURIComponent(title)}` +
    `&dates=${formatGoogleDate(startDate)}/${formatGoogleDate(endDate)}` +
    `&details=${encodeURIComponent(details)}${location ? `&location=${encodeURIComponent(location)}` : ''}`;
};
</script>
