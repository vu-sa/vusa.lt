<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Sveikinimo žodžiai | Ataskaita 2022'"
  >
    <article
      class="grid grid-cols-5 justify-items-center gap-y-4 px-8 lg:px-32"
    >
      <slot></slot>
      <div class="hidden md:block">
        <NButton
          class="top-[50vh] hidden h-fit md:block"
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
import { ArrowCircleRight28Regular } from "@vicons/fluent";
import { NButton, NIcon } from "naive-ui";
import { router, usePage } from "@inertiajs/vue3";
import AtaskaitaLayout from "@/Components/Public/Layouts/AtaskaitaLayout.vue";

const inertiaVisitOnClick = (permalink) => {
  router.visit(
    route("ataskaita2022", {
      lang: usePage().props.app.locale,
      permalink: permalink,
    })
  );
};
</script>
