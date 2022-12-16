<template>
  <Head
    ><title v-if="title">{{ title }}</title></Head
  >
  <!-- Usually maybe for breadcrumb -->
  <slot name="above-header" />
  <NDivider v-if="breadcrumb" />

  <header
    class="flex max-w-7xl flex-row flex-wrap items-center gap-4 overflow-visible"
    :class="{ 'pb-2': title }"
  >
    <Link v-if="!isIndex && backUrl" :href="backUrl">
      <div class="flex">
        <NIcon size="28" :component="ArrowCircleLeft32Regular" /></div
    ></Link>
    <h1 class="mb-0">
      <slot name="title">{{ title }}</slot>
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
  <slot name="below-header"></slot>
  <NDivider
    v-if="title && headerDivider"
    style="margin-top: 0px"
    class="mt-0"
  />

  <div
    class="mt-1 mb-12 grid max-w-7xl grid-flow-row-dense grid-cols-[1fr_auto] gap-x-8 lg:grid-flow-col"
  >
    <FadeTransition appear
      ><main class="col-span w-full overflow-visible">
        <slot></slot></main
    ></FadeTransition>
    <slot name="aside-card"></slot>
  </div>
</template>

<script setup lang="ts">
import { AddCircle32Regular, ArrowCircleLeft32Regular } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { NDivider, NIcon } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  breadcrumb?: true;
  createUrl?: string;
  backUrl?: string;
  headerDivider?: boolean;
  title?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});
</script>

<style></style>
