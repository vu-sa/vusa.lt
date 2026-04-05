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
    <div class="grid gap-6 flex-1" style="grid-template-rows: minmax(0, 1fr) auto;">
      <!-- Main content - no overflow here, let content flow naturally -->
      <div class="min-h-0">
        <slot />
      </div>

      <!-- Related models and additional content -->
      <div class="space-y-6">
        <div v-if="relatedModels" class="flex flex-wrap items-center gap-4">
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
import type { Component } from 'vue';

import AdminContentPage from '../AdminContentPage.vue';

import ActivityLogButton from '@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue';
import FadeTransition from '@/Components/Transitions/FadeTransition.vue';
import RelatedModelButton from '@/Components/Buttons/RelatedModelButton.vue';

const emit = defineEmits<(e: 'change:tab', name: string) => void>();

const props = defineProps<{
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
