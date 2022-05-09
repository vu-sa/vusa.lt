<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg'"
    :title="'Bendruomenė | Ataskaita 2022'"
  >
    <div class="grid md:grid-cols-9 mx-8">
      <div class="hidden lg:block">
        <NButton
          @click="inertiaVisitOnClick('bendruomene')"
          class="hidden md:block top-[50vh] h-fit"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
        >
          <NIcon><ArrowCircleLeft28Regular /></NIcon
        ></NButton>
      </div>
      <article
        class="grid grid-cols-1 lg:grid-cols-2 justify-items-center lg:pr-32 gap-0 gap-y-4 md:gap-6 py-6 rounded-lg col-span-9 lg:col-span-7"
      >
        <slot></slot>
      </article>
      <div class="hidden lg:block">
        <NButton
          @click="inertiaVisitOnClick('padeka')"
          class="hidden md:block top-[50vh] h-fit"
          text
          style="font-size: 60px; align-self: flex-start; position: sticky"
        >
          <NIcon><ArrowCircleRight28Regular /></NIcon
        ></NButton>
      </div>
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
