<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Apie VU SA | Ataskaita 2022'"
  >
    <div class="grid grid-cols-8 justify-center justify-items-center">
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('sveikinimai')"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <article
        class="col-span-6 mx-auto flex w-full flex-col items-center justify-items-center gap-y-4 px-8 lg:px-32"
      >
        <slot></slot>
      </article>
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('mvp')"
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
import { NButton, NIcon } from "naive-ui";
import { router, usePage } from "@inertiajs/vue3";
import AtaskaitaLayout from "@/Components/Public/Layouts/AtaskaitaLayout.vue";

const inertiaVisitOnClick = (permalink) => {
  router.visit(
    route("main.ataskaita2022", {
      lang: usePage().props.locale,
      permalink: permalink,
    })
  );
};
</script>
