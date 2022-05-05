<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Sveikinimo žodžiai | Ataskaita 2022'"
  >
    <article class="grid grid-cols-5 justify-items-center px-8 lg:px-32 gap-y-4">
      <slot></slot>
      <NButton
        @click="inertiaVisitOnClick('vu-sa')"
        class="hidden md:block top-[50vh] h-fit"
        text
        style="font-size: 60px; align-self: flex-start; position: sticky"
        ><NIcon><ArrowCircleRight28Regular /></NIcon
      ></NButton>
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
