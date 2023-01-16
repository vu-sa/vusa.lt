<template>
  <AdminContentPage :title="title">
    <template #above-header>
      <template v-if="breadcrumbOptions">
        <AdminBreadcrumbDisplayer
          :options="breadcrumbOptions"
          class="mb-4 w-full"
        />
        <NDivider />
      </template>
    </template>
    <template #after-heading>
      <slot name="after-heading"></slot>
    </template>
    <template #aside-header>
      <div class="flex gap-2">
        <ActivityLogButton :activities="model.activities" />
        <slot name="more-options"></slot>
      </div>
    </template>
    <template #title>
      <slot name="title" />
    </template>
    <slot />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { NDivider } from "naive-ui";
import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import AdminBreadcrumbDisplayer from "./Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import AdminContentPage from "../AdminContentPage.vue";
import type { BreadcrumbOption } from "./Breadcrumbs/AdminBreadcrumbDisplayer.vue";

defineProps<{
  breadcrumbOptions?: BreadcrumbOption[];
  model: Record<string, any>;
  title?: string;
}>();
</script>
