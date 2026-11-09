<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import SectionCard from '@/Components/Patterns/SectionCard.vue';
import HandoverEditor from '@/Features/Admin/Learning/HandoverEditor.vue';
import HandoverNotes from '@/Features/Admin/Learning/HandoverNotes.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import type { WritableHandoverDuty, HandoverNoteData } from '@/Types/learning';

defineProps<{ writable: WritableHandoverDuty[]; received: HandoverNoteData[]; canSeeReps: boolean }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.handover')">
    <LearningTabs :can-see-reps="canSeeReps" />
    <SectionCard v-if="received.length" :title="$t('learning_people.received')">
      <HandoverNotes :notes="received" />
    </SectionCard>
    <section class="space-y-5" aria-labelledby="your-handover-notes">
      <h2 id="your-handover-notes" class="text-lg font-bold uppercase tracking-wide">{{ $t('learning_people.your_notes') }}</h2>
      <SectionCard v-for="duty in writable" :key="duty.dutyId" :title="duty.title">
        <HandoverEditor :duty="duty" />
      </SectionCard>
      <EmptyState v-if="!writable.length" :title="$t('learning_people.no_writable')" />
    </section>
  </OverviewPage>
</template>
