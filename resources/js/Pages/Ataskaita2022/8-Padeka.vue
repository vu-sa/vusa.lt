<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Padėka | Ataskaita 2022'"
  >
    <div class="mx-8 grid grid-cols-6">
      <div class="hidden lg:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
          @click="inertiaVisitOnClick('sritys')"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <article
        class="col-span-6 grid grid-cols-4 justify-items-center gap-y-4 pr-8 md:col-span-5 lg:pr-32"
      >
        <slot></slot>
      </article>
    </div>
  </AtaskaitaLayout>
</template>

<script setup lang="ts">
import { ArrowCircleLeft28Regular } from "@vicons/fluent";
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
