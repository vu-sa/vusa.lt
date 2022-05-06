<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Apie VU SA | Ataskaita 2022'"
  >
    <div class="grid grid-cols-8 justify-items-center justify-center">
      <NButton
        @click="inertiaVisitOnClick('sveikinimai')"
        class="hidden md:block top-[50vh] h-fit"
        text
        style="font-size: 60px; align-self: flex-start; position: sticky"
      >
        <NIcon><ArrowCircleLeft28Regular /></NIcon
      ></NButton>
      <article
        class="flex flex-col justify-items-center px-8 lg:px-32 gap-y-4 mx-auto items-center col-span-6 w-full"
      >
        <slot></slot>
      </article>
      <NButton
        @click="inertiaVisitOnClick('mvp')"
        class="hidden md:block top-[50vh] h-fit"
        text
        style="font-size: 60px; align-self: flex-start; position: sticky"
        ><NIcon><ArrowCircleRight28Regular /></NIcon
      ></NButton>
    </div>
  </AtaskaitaLayout>
</template>

<script setup>
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";
import { NButton, NIcon } from "naive-ui";
import { ArrowCircleLeft28Regular, ArrowCircleRight28Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";

const inertiaVisitOnClick = (permalink) => {
  Inertia.visit(
    route("main.ataskaita2022", {
      lang: usePage().props.value.locale,
      permalink: permalink,
    })
  );
};
</script>
