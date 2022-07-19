<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Padėka | Ataskaita 2022'"
  >
    <div class="grid grid-cols-6 mx-8">
      <div class="hidden lg:block">
        <NButton
          class="hidden md:block top-[50vh] h-fit"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('sritys')"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <article
        class="grid grid-cols-4 justify-items-center pr-8 lg:pr-32 gap-y-4 col-span-6 md:col-span-5"
      >
        <slot></slot>
      </article>
    </div>
  </AtaskaitaLayout>
</template>

<script setup lang="ts">
import { ArrowCircleLeft28Regular } from "@vicons/fluent";
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
