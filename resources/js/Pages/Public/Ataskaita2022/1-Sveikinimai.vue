<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Sveikinimo žodžiai | Ataskaita 2022'"
  >
    <article
      class="grid grid-cols-5 justify-items-center px-8 lg:px-32 gap-y-4"
    >
      <slot></slot>
      <div class="hidden md:block">
        <NButton
          class="hidden md:block top-[50vh] h-fit"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('vu-sa')"
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
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon } from "naive-ui";
import { usePage } from "@inertiajs/inertia-vue3";
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";

const inertiaVisitOnClick = (permalink) => {
  Inertia.visit(
    route("main.ataskaita2022", {
      lang: usePage().props.value.locale,
      permalink: permalink,
    })
  );
};
</script>
