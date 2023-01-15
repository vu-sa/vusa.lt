<template>
  <Head
    ><title v-if="title">{{ title }}</title></Head
  >
  <!-- Usually maybe for breadcrumb -->
  <slot name="above-header" />

  <header
    class="flex max-w-7xl flex-row flex-wrap items-center gap-4 overflow-visible"
    :class="{ 'pb-2': title }"
  >
    <NButton v-if="!isIndex && backUrl" text class="flex" @click="back">
      <template #icon>
        <NIcon size="28" :component="ArrowCircleLeft32Regular"
      /></template>
    </NButton>
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
    class="mt-4 grid max-w-7xl grid-flow-row-dense grid-cols-[1fr_auto] gap-x-8 lg:grid-flow-col"
  >
    <FadeTransition appear
      ><div class="col-span min-h-full w-full overflow-visible">
        <slot /></div
    ></FadeTransition>
    <slot name="aside-card"></slot>
  </div>
</template>

<script setup lang="ts">
import { AddCircle32Regular, ArrowCircleLeft32Regular } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/vue3";
import { NButton, NDivider, NIcon } from "naive-ui";
import { computed } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  breadcrumb?: true;
  backUrl?: string;
  createUrl?: string;
  headerDivider?: boolean;
  title?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});

const back = () => window.history.back();
</script>
