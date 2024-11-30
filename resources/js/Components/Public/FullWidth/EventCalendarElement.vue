<template>

  <Head>
    <link rel="preload" href="/images/photos/pirmakursiu_stovykla_kaune.jpg" as="image">
    <link rel="preload" href="/images/photos/vu.jpg" as="image">
    <link rel="preload" href="/images/photos/stovykla.jpg" as="image">
  </Head>

  <div class="mt-8 flex flex-col-reverse gap-4 lg:mt-32 lg:flex-row">
    <div
      class="typography flex w-fit max-w-prose flex-col items-center justify-center text-base lg:h-4/5 lg:w-1/2 lg:items-start 2xl:w-3/4">
      <p v-if="$page.props.app.locale === 'lt'" class="text-2xl font-bold lg:w-2/3">
        Sek visus VU studentų renginius bei įvykius
        <span class="text-vusa-red">čia!</span>
      </p>
      <p v-else class="text-2xl font-bold lg:w-2/3">
        Follow Vilnius University activities for students
        <span class="text-vusa-red">here!</span>
      </p>

      <p v-if="$page.props.app.locale === 'lt'" class="w-4/5">
        Arba nesuk galvos ir
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true">sinchronizuok</NButton>
        </span>
        <strong>studentų kalendorių</strong> į „Google“ arba „Outlook“ ..? 🗓
      </p>

      <p v-else class="w-4/5">
        Or you can
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true">sync</NButton>
        </span>
        <strong>this student calendar</strong> to „Google“ or „Outlook“ ..? 🗓
      </p>
    </div>
      <CalendarSyncModal v-model:show-modal="showModal" @close="showModal = false" />
    <div class="relative mx-auto">
      <div class="relative flex w-fit items-center justify-center lg:top-4">
        <template v-if="showPhotos">
          <img
            class="absolute -left-32 top-8 max-w-48 rounded-lg object-cover shadow-xl blur brightness-50 lg:-top-24 lg:max-w-64"
            src="/images/photos/vu.jpg">
          <img
            class="absolute -left-16 top-12 z-[1] max-w-48 rounded-lg object-cover shadow-xl blur-sm brightness-75 lg:-top-12 lg:max-w-64"
            src="/images/photos/stovykla.jpg">
          <img
            class="absolute left-12 top-14 z-[1] rounded-lg object-cover shadow-2xl brightness-125 contrast-100 sm:left-24 md:left-32 lg:left-48 lg:max-w-64"
            src="/images/photos/pirmakursiu_stovykla_kaune.jpg">
        </template>
        <FadeTransition>
          <EventCalendar class="relative z-[5]" :calendar-events="calendar" :locale="$page.props.app.locale" />
        </FadeTransition>
      </div>
    </div>
  </div>
  <div v-if="upcomingEvents.length > 0" class="my-8">
    <h2 class="mb-4 text-center lg:text-start">
      {{ $t('Artėjantys renginiai') }}
    </h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <a v-for="event in eventsByHavingImages.hasImages" :key="event.id" class="hidden w-full md:block" :href="route('calendar.event', {
        calendar: event.id,
        lang: $page.props.app.locale,
      })
        ">
        <CalendarCard hide-footer :calendar-event="event" />
      </a>
      <div v-for="event in eventsByHavingImages.noImages" :key="event[0].id" class="hidden h-full md:block">
        <div class="grid grid-rows-2 items-start gap-4">
          <a v-for="subEvent in event" :key="subEvent.id" :href="route('calendar.event', {
            calendar: subEvent.id,
            lang: $page.props.app.locale,
          })
            ">
            <CalendarCard hide-footer :calendar-event="subEvent" />
          </a>
        </div>
      </div>
      <div class="block w-fit md:hidden">
        <div class="flex flex-col gap-4">
          <a v-for="event in upcomingEvents.slice(0, 3)" :key="event.id" :href="route('calendar.event', {
            calendar: event.id,
            lang: $page.props.app.locale,
          })
            ">
            <CalendarCard :calendar-event="event" />
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { NButton } from "naive-ui";
import { computed, ref } from "vue";

import CalendarCard from "@/Components/Calendar/CalendarCard.vue";
import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const props = defineProps<{
  calendar: Array<App.Entities.Calendar>;
  showPhotos: boolean;
  upcomingEvents: Array<App.Entities.Calendar>;
}>();

const showModal = ref(false);

const eventsByHavingImages = computed(() => {
  const data = {
    hasImages: props.upcomingEvents.filter((event) => event?.images.length > 0),
    // Generate array of arrays of two elements, because images take more space
    noImages: props.upcomingEvents.filter((event) => event?.images.length === 0).reduce((acc, event, index) => {
      if (index % 2 === 0) {
        acc.push([event]);
      } else {
        acc[acc.length - 1].push(event);
      }
      return acc;
    }, []),
  };

  // if data noImages last array has one element, remove array
  if (data.noImages.length > 0 && data.noImages[data.noImages.length - 1].length === 1) {
    data.noImages.pop();
  }

  return data;
});
</script>
