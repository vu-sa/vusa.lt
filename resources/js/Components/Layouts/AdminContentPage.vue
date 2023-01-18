<template>
  <Head
    ><title v-if="title">{{ title }}</title></Head
  >
  <!-- Usually maybe for breadcrumb -->
  <slot name="above-header" />

  <header
    v-if="title"
    class="col-span-2 mb-4 flex max-w-7xl flex-row items-center gap-4 pr-8"
    :class="{ 'pb-2': title }"
  >
    <NButton
      v-if="!isIndex && backUrl"
      style="margin-top: 0.1rem"
      quaternary
      size="small"
      @click="back"
    >
      <template #icon>
        <NIcon size="28" :component="ChevronLeft24Filled"
      /></template>
    </NButton>
    <h1 class="mb-0 whitespace-nowrap">
      <slot name="title">{{ title }}</slot>
    </h1>
    <Link v-if="isIndex && createUrl" :href="createUrl">
      <div class="flex">
        <NIcon size="28" :component="AddCircle32Regular" /></div
    ></Link>
    <aside class="w-full">
      <NScrollbar x-scrollable>
        <div class="flex flex-row items-center justify-between gap-2">
          <slot name="after-heading"></slot>
          <aside class="ml-auto">
            <slot name="aside-header"></slot>
          </aside>
        </div>
      </NScrollbar>
    </aside>
  </header>
  <slot name="below-header"></slot>
  <NDivider
    v-if="title && headerDivider"
    style="margin-top: 0px"
    class="mt-0"
  />

  <div class="max-w-5xl pr-4" :class="{ 'col-span-2': !aside }">
    <FadeTransition appear
      ><div class="overflow-visible">
        <slot /></div
    ></FadeTransition>
  </div>
  <div v-if="aside" class="sticky top-4 px-2">
    <slot name="aside-card"></slot>
  </div>
</template>

<script setup lang="ts">
import { AddCircle32Regular, ChevronLeft24Filled } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/vue3";
import { NButton, NDivider, NIcon, NScrollbar } from "naive-ui";
import { computed } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  aside?: boolean;
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
