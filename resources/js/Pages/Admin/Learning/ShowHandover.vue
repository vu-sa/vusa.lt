<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import HandoverNotes from '@/Features/Admin/Learning/HandoverNotes.vue';
import type { WritableHandoverDuty, HandoverNoteData } from '@/Types/learning';

defineProps<{ writable: WritableHandoverDuty[]; received: HandoverNoteData[] }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.handover')">
    <HandoverNotes :notes="received" :writable="writable" />
    <EmptyState v-if="!writable.length && !received.length" :title="$t('learning_people.no_writable')" />
  </OverviewPage>
</template>
