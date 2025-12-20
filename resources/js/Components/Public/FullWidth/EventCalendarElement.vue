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
        <Button as-child>
          <Link :href="route('calendar.list', { lang: $page.props.app.locale })" prefetch>
            <IFluentCalendarLtr20Regular class="w-4 h-4 mr-2" />
            {{ $t("Visi renginiai") }}
          </Link>
        </Button>

        <Button variant="outline" @click="showModal = true">
          <IFluentArrowSync20Regular class="w-4 h-4 mr-2" />
          {{ $t("Sinchronizuoti kalendorių") }}
        </Button>
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
            <Skeleton class="w-full h-96 rounded-lg" />
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
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Skeleton from "@/Components/ui/skeleton/Skeleton.vue";
import { useCalendarFetch } from "@/Services/ContentService";

const showModal = ref(false);

// Use the ContentService to fetch calendar data
const { calendar, loading, error } = useCalendarFetch();

// Removed debug console.log for production code

</script>
