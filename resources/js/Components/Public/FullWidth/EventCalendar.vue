<template>
  <div class="group relative">
    <Head>
      <link
        rel="preload"
        href="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
        as="image"
      />
    </Head>

    <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
    <ShapeDivider1
      class="absolute -bottom-2 z-10 rotate-180 lg:-bottom-1"
    ></ShapeDivider1>
    <div class="relative">
      <img
        src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
        class="mt-2 h-32 w-full object-cover brightness-75 lg:my-1 lg:h-64"
        style="object-position: 0% 35%"
      />
    </div>
  </div>
  <div
    class="mx-8 mt-4 flex flex-col justify-center gap-4 lg:mx-16 lg:flex-row lg:px-16"
  >
    <div class="prose-sm sm:prose">
      <p class="text-2xl font-bold lg:w-4/5">
        Sek visus renginius studentams
        <span class="font-extrabold text-vusa-red">čia! 🗓</span>
      </p>
      <p class="w-4/5">Kalendorius atnaujinamas kiekvieną dieną!</p>
    </div>
    <div class="flex justify-center">
      <Calendar
        class="shadow-md"
        :attributes="calendarAttributes"
        color="red"
        locale="lt"
      ></Calendar>
    </div>
  </div>
  <NDivider />
</template>

<script setup lang="ts">
import { Calendar } from "v-calendar";
import { Head } from "@inertiajs/inertia-vue3";
import { NDivider } from "naive-ui";

import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps<{
  calendar: Array<App.Models.Calendar>;
}>();

const calendarAttributes = props.calendar.map((event) => ({
  dates: new Date(event.date.replace(/-/g, "/")),
  dot: event.category == "freshmen-camps" ? "yellow" : event.category ?? "red",
  popover: {
    label: event.title,
    isInteractive: true,
  },
}));

// add today to the calendar
calendarAttributes.push({
  dates: new Date(),
  highlight: "red",
});
</script>

<style scoped>
.vc-container {
  font-family: "Inter", sans-serif !important;
}
</style>
