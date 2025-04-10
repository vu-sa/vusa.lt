<template>
  <div>
    <!-- Events counter with improved readability -->
    <div class="my-4 text-zinc-500 dark:text-zinc-400">
      {{ $t("Rasta :count renginių", { count: events.total }) }}
      <template v-if="events.last_page > 1">
        | {{ $t("Psl.") }} {{ events.current_page }} / {{ events.last_page }}
      </template>
    </div>

    <!-- List view of events -->
    <div class="mb-8">
      <div v-if="events.data.length > 0"
        class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div v-for="(event, index) in events.data" :key="event.id"
          :class="[{ 'border-t border-zinc-200 dark:border-zinc-700': index > 0 }, { 'opacity-80': tab === 'past' }]"
          class="event-row flex flex-col items-start p-4 sm:flex-row sm:items-center">
          <!-- Calendar date badge -->
          <div class="mb-3 flex-shrink-0 rounded-md border px-3 py-2 text-center sm:mb-0 sm:mr-4 w-18" :class="tab === 'upcoming' ?
            'border-red-100/70 bg-red-50/70 dark:border-red-900/50 dark:bg-red-950/30' :
            'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800'">
            <div class="text-xs font-semibold uppercase" :class="tab === 'upcoming' ?
              'text-red-600/80 dark:text-red-400/80' :
              'text-zinc-500 dark:text-zinc-400'">
              {{ formatMonth(event.date) }}
            </div>
            <div class="text-2xl font-bold" :class="tab === 'upcoming' ?
              'text-red-700/90 dark:text-red-300/90' :
              'text-zinc-700 dark:text-zinc-300'">
              {{ formatDay(event.date) }}
            </div>
            <!-- Show year for past events with clear separation -->
            <div v-if="tab === 'past'" class="mb-1 text-xs font-medium text-zinc-600 dark:text-zinc-400">
              {{ formatYear(event.date) }}
            </div>
            <!-- Add separator when showing year (past events) -->
            <div v-if="tab === 'past'" class="my-1 h-px w-full bg-zinc-200 dark:bg-zinc-700" />
            <div class="text-xs flex flex-col" :class="tab === 'upcoming' ?
              'text-red-600/80 dark:text-red-400/80' :
              'text-zinc-600 dark:text-zinc-400'">
              <span>{{ formatTime(event.date) }}</span>
              <template v-if="event.end_date && isSameDay(new Date(event.date), new Date(event.end_date))">
                <div class="flex items-center justify-center">
                  <svg class="w-3 h-3 my-px" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z"/>
                  </svg>
                </div>
                <span>{{ formatTime(event.end_date) }}</span>
              </template>
            </div>

          </div>

          <!-- Event content -->
          <div class="flex-grow">
            <div class="mb-2 flex flex-wrap items-center gap-2">
              <!-- Category badge if available -->
              <span v-if="event.category"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" 
                :class="tab === 'upcoming' ?
                  'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/70 dark:text-zinc-300' :
                  'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'">
                {{ event.category.name }}
              </span>
              <!-- Tenant badge if available -->
              <span v-if="event.tenant" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="tab === 'upcoming' ?
                  'bg-blue-100/70 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300/90' :
                  'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'">
                {{ event.tenant.shortname }}
              </span>
            </div>

            <h3 class="mb-1 font-semibold hover:text-red-600 dark:hover:text-red-400" :class="tab === 'upcoming' ?
              'text-zinc-900 dark:text-zinc-100' :
              'text-zinc-700 dark:text-zinc-300'">
              <Link :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })">
              {{ event.title }}
              </Link>
            </h3>

            <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm" :class="tab === 'upcoming' ?
              'text-zinc-600 dark:text-zinc-400' :
              'text-zinc-500 dark:text-zinc-400'">
              <!-- Date range indicator if multi-day event -->
              <div v-if="event.end_date && !isSameDay(new Date(event.date), new Date(event.end_date))" 
                   class="flex items-center gap-1">
                <i :class="tab === 'upcoming' ?
                  'text-red-600/80 dark:text-red-400/80' :
                  'text-zinc-500 dark:text-zinc-400'">
                  <IFluentCalendarLtr16Regular />
                </i>
                <span>{{ $t("iki") }} {{ formatMonth(event.end_date) }} {{ formatDay(event.end_date) }}</span>
              </div>
              
              <!-- Location if available -->
              <div v-if="event.location" class="flex items-center gap-1">
                <i :class="tab === 'upcoming' ?
                  'text-red-600/80 dark:text-red-400/80' :
                  'text-zinc-500 dark:text-zinc-400'">
                  <IFluentLocation16Regular />
                </i>
                <span>{{ event.location }}</span>
              </div>

              <!-- Organizer if available -->
              <div v-if="event.organizer" class="flex items-center gap-1">
                <i :class="tab === 'upcoming' ?
                  'text-red-600/80 dark:text-red-400/80' :
                  'text-zinc-500 dark:text-zinc-400'">
                  <IFluentPeopleTeam16Regular />
                </i>
                <span>{{ event.organizer }}</span>
              </div>
            </div>
          </div>

          <!-- Action buttons -->
          <div
            class="mt-3 flex w-full flex-wrap gap-2 sm:mt-0 sm:w-auto sm:flex-shrink-0 sm:flex-col sm:items-end sm:justify-center">
            <NButton size="small" tag="a" :secondary="tab === 'past'"
              :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })">
              {{ tab === 'upcoming' ? $t("Daugiau") : $t("Peržiūrėti") }}
            </NButton>
            <div v-if="tab === 'upcoming'" class="flex gap-2">
              <NButton v-if="event.googleLink" size="small" secondary tag="a" :href="event.googleLink" target="_blank">
                <template #icon>
                  <IMdiGoogle />
                </template>
              </NButton>
              <NButton v-if="event.facebook_url" size="small" secondary tag="a" :href="event.facebook_url"
                target="_blank">
                <template #icon>
                  <IMdiFacebook />
                </template>
              </NButton>
            </div>
          </div>
        </div>
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
import { NButton, NPagination } from "naive-ui";
import { Link, usePage } from "@inertiajs/vue3";

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
const onPageChange = (page) => {
  emit('pageChange', page);
};

// Formatting functions for date display
const formatMonth = (dateString: string) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat(document.documentElement.lang, {
    month: 'short'
  }).format(date);
};

const formatDay = (dateString: string) => {
  const date = new Date(dateString);
  return date.getDate();
};

const formatTime = (dateString: string) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat(document.documentElement.lang, {
    hour: 'numeric',
    minute: 'numeric'
  }).format(date);
};

// Format year for past events
const formatYear = (dateString: string) => {
  const date = new Date(dateString);
  return date.getFullYear();
};

// Helper function to check if two dates are on the same day
const isSameDay = (date1: Date, date2: Date): boolean => {
  return date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate();
};
</script>
