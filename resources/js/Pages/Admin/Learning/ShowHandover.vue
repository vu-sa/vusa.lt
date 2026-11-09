<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import type { WritableHandoverDuty, HandoverNoteData } from '@/Types/learning';

defineProps<{ writable: WritableHandoverDuty[]; received: HandoverNoteData[]; canSeeReps: boolean }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.handover')">
    <LearningTabs :can-see-reps="canSeeReps" />
    <EmptyState :title="$t('learning.empty.title')" :description="$t('learning.empty.description')" />
  </OverviewPage>
</template>
