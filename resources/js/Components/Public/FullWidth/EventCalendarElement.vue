<template>
  <div class="mb-16 mt-24 flex flex-col gap-6 lg:flex-row text-zinc-900 dark:text-zinc-50 items-center">
    <div
      class="typography flex w-fit max-w-prose flex-col items-center justify-center text-base lg:h-4/5 lg:w-1/2 lg:items-start 2xl:w-3/4">
      <p v-if="$page.props.app.locale === 'lt'" class="text-2xl font-bold lg:w-2/3">
        Sek visus VU studentų renginius bei įvykius!
      </p>
      <p v-else class="text-2xl font-bold lg:w-2/3">
        Follow Vilnius University activities for students!
      </p>

      <div class="flex gap-4">
        <Link :href="route('calendar.list', { lang: $page.props.app.locale })">
        <NButton type="primary">
          <template #icon>
            <IFluentCalendarLtr20Regular />
          </template>
          {{ $t("Visi renginiai") }}
        </NButton>
        </Link>

        <NButton secondary @click="showModal = true">
          <template #icon>
            <IFluentArrowSync20Regular />
          </template>
          {{ $t("Sinchronizuoti kalendorių") }}
        </NButton>
      </div>
    </div>

    <CalendarSyncModal v-model:show-modal="showModal" @close="showModal = false" />
    <div class=" mx-auto">
      <div class="flex w-fit items-center justify-center">
        <!-- <template v-if="showPhotos">
          <img alt="Vilniaus universitetas"
            class="absolute -left-32 top-8 max-w-48 rounded-lg object-cover shadow-xl blur-sm brightness-50 lg:-top-24 lg:max-w-64"
            src="/images/photos/vu.jpg">
          <img alt="Pirmakursių stovykla"
            class="absolute -left-16 top-12 z-1 max-w-48 rounded-lg object-cover shadow-xl blur-xs brightness-75 lg:-top-12 lg:max-w-64"
            src="/images/photos/stovykla.jpg">
          <img alt="Pirmakursių stovykla Kaune"
            class="absolute left-12 top-14 z-1 rounded-lg object-cover shadow-2xl brightness-125 contrast-100 sm:left-24 md:left-32 lg:left-48 lg:max-w-64"
            src="/images/photos/pirmakursiu_stovykla_kaune.jpg">
</template> -->
        <FadeTransition>
          <div v-if="loading" class="w-full h-96 flex items-center justify-center">
            <div class="animate-pulse flex flex-col gap-3 items-center">
              <div class="h-12 w-12 rounded-full bg-zinc-200 dark:bg-zinc-700" />
              <div class="h-5 w-64 rounded bg-zinc-200 dark:bg-zinc-700" />
              <div class="h-32 w-96 rounded bg-zinc-200 dark:bg-zinc-700" />
            </div>
          </div>
          <div v-else-if="error" class="text-red-500 p-4 rounded-lg border border-red-300">
            {{ $t("Nepavyko užkrauti kalendoriaus įvykių") }}
          </div>
          <EventCalendar v-else class="z-5" :calendar-events="calendar" :locale="$page.props.app.locale" />
        </FadeTransition>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { NButton } from "naive-ui";
import { ref } from "vue";

import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const showModal = ref(false);

import { useCalendarFetch } from "@/Services/ContentService";
import { Link } from "@inertiajs/vue3";

// Use the ContentService to fetch calendar data
const { calendar, loading, error } = useCalendarFetch();

// Removed debug console.log for production code

</script>
