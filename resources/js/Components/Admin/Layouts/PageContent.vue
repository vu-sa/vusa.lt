<template>
  <Head
    ><title>{{ title }}</title></Head
  >

  <header
    class="flex max-w-7xl flex-row flex-wrap items-center gap-4 overflow-auto pb-4"
  >
    <Link v-if="!isIndex && backUrl" :href="backUrl">
      <div class="flex">
        <NIcon size="28" :component="ArrowCircleLeft32Regular" /></div
    ></Link>
    <h1 class="mb-0">
      {{ title }}
    </h1>
    <Link v-if="isIndex && createUrl" :href="createUrl">
      <div class="flex">
        <NIcon size="28" :component="AddCircle32Regular" /></div
    ></Link>
    <slot name="after-heading"></slot>
    <aside class="ml-auto font-bold transition-colors md:text-xs">
      <slot name="aside-header"></slot>
    </aside>
    <slot name="below-header"></slot>
  </header>

  <div
    class="mt-1 grid max-w-7xl grid-flow-row-dense grid-cols-[1fr_auto] gap-x-8 lg:grid-flow-col"
  >
    <FadeTransition appear
      ><main class="col-span w-full overflow-auto">
        <slot></slot></main
    ></FadeTransition>
    <slot name="aside-card"></slot>
  </div>
</template>

<script setup lang="ts">
import { AddCircle32Regular, ArrowCircleLeft32Regular } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

defineProps<{
  createUrl?: string;
  backUrl?: string;
  title?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});
</script>
