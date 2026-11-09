<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import type { PageProps } from '@/Types';

import LearningTabs from './LearningTabs.vue';

defineProps<{ canSeeReps?: boolean }>();
const page = usePage<PageProps>();
</script>

<template>
  <div class="space-y-6 pt-6" data-slot="learning-page-frame">
    <LearningTabs :can-see-reps="canSeeReps ?? page.props.learningNavigation?.canSeeReps ?? false" :can-manage="page.props.learningNavigation?.canManage ?? false" />
    <slot />
  </div>
</template>
