<template>
  <div
    class="col-span-full mt-24 grid grid-flow-row auto-rows-max grid-cols-6 md:col-span-8 md:mx-8 md:mt-6"
  >
    <header class="col-span-4 mt-4 mb-5 flex flex-row items-center gap-4">
      <Link v-if="!isIndex && backUrl" class="" :href="backUrl">
        <div class="flex">
          <NIcon size="28"
            ><ArrowCircleLeft32Regular
              class="h-6 w-6 duration-200 hover:text-red-800"
          /></NIcon></div
      ></Link>
      <h1 class="mb-0 text-gray-800">
        <slot name="header"></slot>
      </h1>
      <Link v-if="isIndex && createUrl" class="" :href="createUrl">
        <div class="flex">
          <NIcon size="28"
            ><AddCircle32Regular
              class="h-6 w-6 duration-200 hover:text-red-800"
          /></NIcon></div
      ></Link>
      <aside class="ml-auto inline-block font-bold duration-200 md:text-xs">
        <slot name="aside-header"></slot>
      </aside>
    </header>
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { AddCircle32Regular, ArrowCircleLeft32Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

defineProps<{
  createUrl?: string;
  backUrl?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});
</script>
