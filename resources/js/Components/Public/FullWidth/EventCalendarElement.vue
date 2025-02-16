<template>
  <Head>
    <link rel="preload" href="/images/photos/pirmakursiu_stovykla_kaune.jpg" as="image">
    <link rel="preload" href="/images/photos/vu.jpg" as="image">
    <link rel="preload" href="/images/photos/stovykla.jpg" as="image">
  </Head>

  <div class="mt-8 flex flex-col-reverse gap-4 lg:flex-row">
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
          <EventCalendar class="relative z-5" :calendar-events="calendar" :locale="$page.props.app.locale" />
        </FadeTransition>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import { NButton } from "naive-ui";
import { ref } from "vue";

import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const showModal = ref(false);

const calendar = await fetch(
  route("api.calendar.tenant.index", {
    lang: usePage().props.app.locale,
    tenant: usePage().props.tenant?.alias,
  })
).then((response) => response.json());
</script>
