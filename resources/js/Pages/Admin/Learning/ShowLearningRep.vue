<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import type { LearningPathData } from '@/Types/learning';

defineProps<{ path: LearningPathData; rep: { id: string; name: string }; canSeeReps: boolean }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.rep')" :eyebrow="rep.name">
    <LearningTabs :can-see-reps="canSeeReps" />
    <LearningPathView :path="path" readonly />
  </OverviewPage>
</template>
