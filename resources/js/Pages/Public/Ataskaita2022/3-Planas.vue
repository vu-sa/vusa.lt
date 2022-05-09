<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/vu.jpg'"
    :title="'Metų veiklos planas | Ataskaita 2022'"
  >
    <article class="grid grid-cols-8 justify-items-center px-8 lg:px-32 gap-y-4">
      <div class="hidden md:block">
        <NButton
          @click="inertiaVisitOnClick('vu-sa')"
          class="hidden md:block top-[50vh] h-fit"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <div class="max-w-[80ch] prose col-span-8 md:col-span-6">
        <div class="bg-white py-6 px-12 rounded-lg">
          <slot></slot>
        </div>
      </div>
      <div class="hidden md:block">
        <NButton
          @click="inertiaVisitOnClick('studijos')"
          class="hidden md:block top-[50vh] h-fit"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          ><NIcon><ArrowCircleRight28Regular /></NIcon
        ></NButton>
      </div>
    </article>
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
