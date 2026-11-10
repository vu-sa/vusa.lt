<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import StatTile from '@/Components/Patterns/StatTile.vue';
import LearningRepList from '@/Features/Admin/Learning/LearningRepList.vue';
import type { LearningRepresentative, LearningRepsSummary } from '@/Types/learning';

defineProps<{ reps: LearningRepresentative[]; summary: LearningRepsSummary }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.reps')">
    <div class="grid gap-x-6 gap-y-5 sm:grid-cols-2 xl:grid-cols-4">
      <StatTile :value="summary.representatives" :label="$t('learning_people.representatives')" />
      <StatTile :value="summary.essentialsFinished" :label="$t('learning_people.finished')" />
      <StatTile :value="summary.notStarted" :label="$t('learning_people.not_started')" />
      <StatTile :value="`${summary.averageEssentialProgress}%`" :label="$t('learning_people.average')" />
    </div>
    <LearningRepList :reps="reps" />
  </OverviewPage>
</template>
