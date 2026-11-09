<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import HandoverNotes from '@/Features/Admin/Learning/HandoverNotes.vue';
import SectionCard from '@/Components/Patterns/SectionCard.vue';
import type { HandoverNoteData, LearningPathData } from '@/Types/learning';

defineProps<{ path: LearningPathData; rep: { id: string; name: string }; notes: HandoverNoteData[]; canSeeReps: boolean }>();
</script>

<template>
  <OverviewPage :title="rep.name" :eyebrow="$t('learning_people.rep_path')">
    <LearningTabs :can-see-reps="canSeeReps" />
    <p class="text-sm tabular-nums text-muted-foreground">{{ $t('learning_people.essentials') }} {{ path.totals.essentialDone }}/{{ path.totals.essentialTotal }} · {{ $t('learning_people.total') }} {{ path.totals.allDone }}/{{ path.totals.allTotal }}</p>
    <LearningPathView :path="path" readonly />
    <SectionCard v-if="notes.length" :title="$t('learning_people.handover_notes')">
      <HandoverNotes :notes="notes" />
    </SectionCard>
  </OverviewPage>
</template>
