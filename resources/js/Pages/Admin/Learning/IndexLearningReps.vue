<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import type { LearningRepresentative, LearningRepsSummary } from '@/Types/learning';

defineProps<{ reps: LearningRepresentative[]; summary: LearningRepsSummary; canSeeReps: boolean }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.reps')">
    <LearningTabs :can-see-reps="canSeeReps" />
    <EmptyState :title="$t('learning.empty.title')" :description="$t('learning.empty.description')" />
  </OverviewPage>
</template>
