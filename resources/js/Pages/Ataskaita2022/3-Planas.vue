<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/vu.jpg'"
    :title="'Metų veiklos planas | Ataskaita 2022'"
  >
    <article
      class="grid grid-cols-8 justify-items-center gap-y-4 px-8 lg:px-32"
    >
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('vu-sa')"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <div class="prose col-span-8 max-w-[80ch] md:col-span-6">
        <div class="rounded-lg bg-white py-6 px-12">
          <slot></slot>
        </div>
      </div>
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('studijos')"
          ><NIcon><ArrowCircleRight28Regular /></NIcon
        ></NButton>
      </div>
    </article>
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
      lang: usePage().props.app.locale,
      permalink: permalink,
    })
  );
};
</script>
