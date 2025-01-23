<template>
  <AdminContentPage :title>
    <template #above-header>
      <template v-if="breadcrumbOptions">
        <AdminBreadcrumbDisplayer :options="breadcrumbOptions" class="mb-4 w-full" />
      </template>
    </template>
    <template #after-heading>
      <slot name="after-heading" />
    </template>
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ActivityLogButton :activities="model.activities" />
        <slot name="more-options" />
      </div>
    </template>
    <template #title>
      <slot name="title" />
    </template>
    <div class="grid grid-rows-[minmax(300px,50vh)_minmax(450px,auto)] gap-4">
      <div class="overflow-y-auto">
        <slot />
      </div>
      <div>
        <div v-if="relatedModels" class="flex-items mb-6 flex gap-4">
          <RelatedModelButton v-for="related in relatedModels" :key="related.name" :name="$t(related.name)"
            :icon="related.icon" :count="related.count" :disabled="related.disabled"
            :active="currentTab === related.name" @click="$emit('change:tab', related.name)" />
        </div>
        <FadeTransition mode="out-in">
          <slot name="below" />
        </FadeTransition>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import type { Component } from "vue";

import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import AdminBreadcrumbDisplayer from "./Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import AdminContentPage from "../AdminContentPage.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import RelatedModelButton from "@/Components/Buttons/RelatedModelButton.vue";
import type { BreadcrumbOption } from "./Breadcrumbs/AdminBreadcrumbDisplayer.vue";

defineEmits<{
  (e: "change:tab", name: string): void;
}>();

defineProps<{
  breadcrumbOptions?: BreadcrumbOption[];
  currentTab?: string;
  model: Record<string, any>;
  relatedModels?: {
    name: string;
    icon?: Component;
    count?: number;
    disabled?: boolean;
  }[];
  title?: string;
}>();
</script>
