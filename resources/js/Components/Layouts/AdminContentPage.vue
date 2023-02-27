<template>
  <Head
    ><title v-if="title">{{ $t(title) }}</title></Head
  >
  <!-- Usually maybe for breadcrumb -->
  <div class="ml-4"><slot name="above-header" /></div>

  <header
    v-if="title"
    class="z-10 col-span-2 m-4 flex max-w-6xl flex-row items-center gap-4 pr-8"
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
    <h1 class="mb-0 inline-flex items-center gap-3 whitespace-nowrap">
      <NIcon v-if="headingIcon" :component="headingIcon" />
      <slot name="title"
        ><NEllipsis style="max-width: 60rem">{{ $t(title) }}</NEllipsis></slot
      >
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

  <div class="ml-4 max-w-6xl" :class="{ 'col-span-2': !aside }">
    <FadeTransition appear
      ><div class="relative overflow-visible">
        <slot /></div
    ></FadeTransition>
  </div>
  <div v-if="aside" class="sticky top-4 px-2">
    <slot name="aside-card"></slot>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { AddCircle32Regular, ChevronLeft24Filled } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/vue3";
import { NButton, NDivider, NEllipsis, NIcon, NScrollbar } from "naive-ui";
import { computed } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  aside?: boolean;
  breadcrumb?: true;
  backUrl?: string;
  createUrl?: string;
  headerDivider?: boolean;
  headingIcon?: any;
  title?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});

const back = () => window.history.back();
</script>
