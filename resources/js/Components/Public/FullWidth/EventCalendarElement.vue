<template>
  <Head>
    <link
      rel="preload"
      href="/images/photos/pirmakursiu_stovykla_kaune.jpg"
      as="image"
    />
    <link rel="preload" href="/images/photos/vu.jpg" as="image" />
    <link rel="preload" href="/images/photos/stovykla.jpg" as="image" />
  </Head>

  <div
    class="mx-auto mt-8 flex max-w-7xl flex-col-reverse gap-4 px-16 lg:mt-32 lg:flex-row lg:px-24 xl:px-40"
  >
    <div
      class="prose prose-sm flex w-fit flex-col items-center justify-center dark:prose-invert lg:h-4/5 lg:w-1/2 lg:items-start 2xl:w-3/4"
    >
      <p
        v-if="$page.props.app.locale === 'lt'"
        class="text-2xl font-bold lg:w-2/3"
      >
        Sek visus VU studentų renginius bei įvykius
        <span class="text-vusa-red">čia!</span>
      </p>
      <p v-else class="text-2xl font-bold lg:w-2/3">
        Follow Vilnius University activities for students
        <span class="text-vusa-red">here!</span>
      </p>

      <p v-if="$page.props.app.locale === 'lt'" class="w-4/5">
        Arba nesuk galvos ir
        <!-- <em>patingėti</em> ir -->
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true"
            >sinchronizuok</NButton
          >
        </span>
        <strong>studentų kalendorių</strong> į „Google“ arba „Outlook“ ..? 🗓
      </p>

      <p v-else class="w-4/5">
        Or you can
        <!-- <em>be lazy</em> and -->
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true"
            >sync</NButton
          >
        </span>
        <strong>this student calendar</strong> to „Google“ or „Outlook“ ..? 🗓
      </p>
    </div>
    <NMessageProvider>
      <CalendarSyncModal
        v-model:show-modal="showModal"
        @close="showModal = false"
      />
    </NMessageProvider>
    <div class="relative mx-auto">
      <div class="relative flex w-fit items-center justify-center lg:top-4">
        <template v-if="showPhotos">
          <img
            class="absolute -left-32 top-8 max-w-[12rem] rounded-lg object-cover shadow-xl blur brightness-50 lg:-top-24 lg:max-w-[16rem]"
            src="/images/photos/vu.jpg"
          />
          <img
            class="absolute -left-16 top-12 z-[1] max-w-[12rem] rounded-lg object-cover shadow-xl blur-sm brightness-75 lg:-top-12 lg:max-w-[16rem]"
            src="/images/photos/stovykla.jpg"
          />
          <img
            class="absolute left-12 top-14 z-[1] rounded-lg object-cover shadow-2xl brightness-125 contrast-100 sm:left-24 md:left-32 lg:left-48 lg:max-w-[16rem]"
            src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
          />
        </template>
        <!-- <img
          class="absolute -top-24 -left-40 rounded-lg object-cover shadow-lg brightness-75"
          src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
        /> -->
        <FadeTransition>
          <div class="my-calendar">
            <EventCalendar
              class="relative z-[5]"
              :calendar-events="calendar"
              :locale="$page.props.app.locale"
              :is-theme-dark="isThemeDark"
            />
          </div>
        </FadeTransition>
      </div>
    </div>
  </div>
  <div
    v-if="upcoming4Events.length > 0"
    class="mx-auto my-8 max-w-7xl px-16 lg:px-24 xl:px-32"
  >
    <h2 class="text-center lg:text-start">Artėjantys renginiai</h2>
    <div class="mx-auto my-8 flex w-fit flex-wrap gap-4 lg:mx-0">
      <Link
        v-for="event in upcoming4Events"
        :key="event.id"
        class="h-fit w-fit"
        :href="
          route('calendar.event', {
            calendar: event.id,
            lang: $page.props.app.locale,
          })
        "
      >
        <CalendarCard :calendar-event="event" />
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { NButton, NMessageProvider } from "naive-ui";
import { ref } from "vue";

import CalendarCard from "@/Components/Calendar/CalendarCard.vue";
import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  calendar: Array<App.Entities.Calendar>;
  isThemeDark: boolean;
  showPhotos: boolean;
  upcoming4Events: Array<App.Entities.Calendar>;
}>();

const showModal = ref(false);
</script>

<style scoped>
.my-calendar :deep(.vc-container.vc-dark) {
  background-color: #29292e;
}

.my-calendar :deep(button.vc-arrow) {
  background-color: transparent;
}
</style>
