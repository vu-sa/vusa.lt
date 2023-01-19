<template>
  <AdminContentPage :title="title">
    <template #above-header>
      <template v-if="breadcrumbOptions">
        <AdminBreadcrumbDisplayer
          :options="breadcrumbOptions"
          class="mb-4 w-full"
        />
      </template>
    </template>
    <template #after-heading>
      <slot name="after-heading"></slot>
    </template>
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ActivityLogButton :activities="model.activities" />
        <slot name="more-options"></slot>
      </div>
    </template>
    <template #title>
      <slot name="title" />
    </template>
    <div class="grid grid-rows-[minmax(300px,_50vh)_auto]">
      <div><slot /></div>
      <div>
        <div v-if="relatedModels" class="flex-items mb-6 flex gap-4">
          <RelatedModelButton
            v-for="related in relatedModels"
            :key="related.name"
            :name="related.name"
            :icon="related.icon"
            :count="related.count"
            :active="currentTab === related.name"
            @click="$emit('change:tab', related.name)"
          ></RelatedModelButton>
        </div>
        <slot name="below" />
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import AdminBreadcrumbDisplayer from "./Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import AdminContentPage from "../AdminContentPage.vue";
import RelatedModelButton from "@/Components/Buttons/RelatedModelButton.vue";
import type { BreadcrumbOption } from "./Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import type { Component } from "vue";

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
  }[];
  title?: string;
}>();
</script>
