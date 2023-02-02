<template>
  <AtaskaitaLayout
    :background-image="'/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg'"
    :title="'Bendruomenė | Ataskaita 2022'"
  >
    <div class="mx-8 grid md:grid-cols-9">
      <div class="hidden lg:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('bendruomene')"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <article
        class="col-span-9 grid grid-cols-1 justify-items-center gap-0 gap-y-4 rounded-lg py-6 md:gap-6 lg:col-span-7 lg:grid-cols-2 lg:pr-32"
      >
        <slot></slot>
      </article>
      <div class="hidden lg:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('padeka')"
        >
          <NIcon><ArrowCircleRight28Regular /></NIcon
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
      lang: usePage().props.app.locale,
      permalink: permalink,
    })
  );
};
</script>
