<template>
  <AdminContentPage :title>
    <template #above-header>
      <!-- Breadcrumbs now managed via composable -->
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
import { onMounted, onUnmounted, watch } from "vue";

import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import AdminContentPage from "../AdminContentPage.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import RelatedModelButton from "@/Components/Buttons/RelatedModelButton.vue";
import { useBreadcrumbs } from "@/Composables/useBreadcrumbs";
import type { BreadcrumbItem } from "@/Composables/useBreadcrumbs";

const emit = defineEmits<{
  (e: "change:tab", name: string): void;
}>();

const props = defineProps<{
  breadcrumbs?: BreadcrumbItem[];
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

// Get breadcrumbs from the composable
const { setBreadcrumbs, clearBreadcrumbs } = useBreadcrumbs();

// Update breadcrumbs whenever the component props change
// This is key for Inertia navigation to update breadcrumbs properly
function updateBreadcrumbs() {
  if (props.breadcrumbs && props.breadcrumbs.length > 0) {
    // Explicitly set breadcrumbs to prevent default behavior in AdminLayout
    setBreadcrumbs(props.breadcrumbs);
  }
}

// Set breadcrumbs when the component mounts and whenever breadcrumbs prop changes
watch(() => props.breadcrumbs, () => {
  updateBreadcrumbs();
}, { immediate: true });

// This ensures breadcrumbs are set even on Inertia page transitions
onMounted(() => {
  updateBreadcrumbs();
});

// Clean up breadcrumbs when component is unmounted
onUnmounted(() => {
  clearBreadcrumbs();
});
</script>
