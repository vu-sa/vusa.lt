<template>
  <Head
    ><title>{{ title }}</title></Head
  >

  <header class="mb-4 flex max-w-7xl flex-row items-center gap-4 text-gray-800">
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
  </header>

  <div class="grid grid-flow-col gap-8">
    <main>
      <FadeTransition appear><slot></slot></FadeTransition>
    </main>
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
