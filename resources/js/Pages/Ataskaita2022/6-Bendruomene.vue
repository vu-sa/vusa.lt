<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Darni universitetinė bendruomenė | Ataskaita 2022'"
  >
    <div class="grid grid-cols-8 justify-center justify-items-center">
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('organizacija')"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <article
        class="col-span-8 mx-auto flex w-full flex-col items-center justify-items-center gap-y-4 px-8 md:col-span-6 lg:px-32"
      >
        <slot></slot>
      </article>
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('sritys')"
          ><NIcon><ArrowCircleRight28Regular /></NIcon
        ></NButton>
      </div>
    </div>
  </AtaskaitaLayout>
</template>

<script setup lang="ts">
import {
  ArrowCircleLeft28Regular,
  ArrowCircleRight28Regular,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon } from "naive-ui";
import { usePage } from "@inertiajs/inertia-vue3";
import AtaskaitaLayout from "@/Components/Public/Layouts/AtaskaitaLayout.vue";

const inertiaVisitOnClick = (permalink) => {
  Inertia.visit(
    route("main.ataskaita2022", {
      lang: usePage().props.value.locale,
      permalink: permalink,
    })
  );
};
</script>
