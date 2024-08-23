<template>
  <article class="mx-auto mt-4">
    <header class="camp-header max-h-96" :class="{
      'py-12 text-white': !hasNoImage,
      'py-4': hasNoImage,
    }" :style="headerImageStyle">
      <div class="mx-auto flex h-full max-w-7xl">
        <div class="h-fit self-end">
          <h1 class="flex items-center px-12 text-4xl font-extrabold text-gray-900 dark:text-zinc-50 lg:text-3xl"
            :class="{ 'text-zinc-50': !hasNoImage }">
            <span>{{
              $page.props.app.locale === "en"
                ? event.extra_attributes?.en?.title ?? event.title
                : event.title
            }}</span>
          </h1>
        </div>
      </div>
    </header>
    <section class="mx-auto mt-8 grid max-w-7xl lg:grid-cols-3">
      <div class="px-12 lg:col-span-2">
        <h2 v-if="event.description !== ''" class="my-4 text-gray-900 dark:text-zinc-50">
          {{ $t("Aprašymas") }}
        </h2>
        <div class="typography text-base leading-7 sm:max-w-[70ch]" v-html="$page.props.app.locale === 'en'
            ? event.extra_attributes?.en?.description ?? event.description
            : event.description
          " />

        <iframe v-if="event.extra_attributes?.video_url" class="mb-8 mt-4 aspect-video h-auto w-full rounded-2xl"
          width="560" height="315" :src="`https://www.youtube-nocookie.com/embed/${event.extra_attributes?.video_url}`"
          title="YouTube video player" frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen />

        <div class="mt-4">
          <NImageGroup :show-toolbar="false">
            <NSpace>
              <NImage v-for="image in event.images" :key="image.id" width="150" :src="image.original_url"
                class="rounded-lg transition hover:shadow-md" lazy />
            </NSpace>
          </NImageGroup>
        </div>
      </div>
      <aside class="-order-1 mx-8 flex lg:order-1">
        <div class="sticky top-32 flex flex-col items-center gap-6">
          <CalendarCard :calendar-event="event" :google-link="googleLink" />
          <div class="hidden flex-col items-center gap-2 lg:flex">
            <NDivider />
            <EventCalendar :calendar-events="calendar" :locale="$page.props.app.locale" />
          </div>
        </div>
      </aside>
    </section>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Head } from "@inertiajs/vue3";
import { NDivider, NImage, NImageGroup, NSpace } from "naive-ui";
import { computed } from "vue";

import CalendarCard from "@/Components/Calendar/CalendarCard.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";

const props = defineProps<{
  event: App.Entities.Calendar;
  calendar: App.Entities.Calendar[];
  googleLink: string;
}>();

// check if image array is empty
const hasNoImage = computed(() => {
  return props.event.images === null || props.event.images.length === 0;
});

const headerImageStyle = computed(() => {
  if (hasNoImage.value) {
    return null;
  }

  return {
    "background-image": `linear-gradient(0deg, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.3)), url(${props.event?.images?.length > 0 ? props.event.images[0].original_url : ""
      })`,
  };
});
</script>

<style scoped>
.camp-header {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
</style>
