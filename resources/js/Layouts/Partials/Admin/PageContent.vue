<template>
  <div
    class="col-span-full md:col-span-8 mt-24 md:mt-6 md:mx-8 grid grid-flow-row auto-rows-max grid-cols-6"
  >
    <header class="mt-4 mb-5 col-span-4 flex flex-row items-center">
      <h1 class="text-gray-800 leading-tight inline-block mr-2 mb-0">
        <slot name="header"></slot>
      </h1>
      <Link v-if="isIndex && createUrl" class="mr-auto" :href="createUrl">
        <div class="flex">
          <NIcon size="28"
            ><AddCircle32Regular
              class="w-6 h-6 hover:text-red-800 duration-200"
          /></NIcon></div
      ></Link>
      <aside class="inline-block font-bold ml-2 duration-200 md:text-xs">
        <slot name="aside-header"></slot>
      </aside>
    </header>
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { AddCircle32Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

defineProps<{
  createUrl?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});
</script>
