<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import HandoverNotes from '@/Features/Admin/Learning/HandoverNotes.vue';
import type { HandoverNoteData, LearningPathData } from '@/Types/learning';

defineProps<{ path: LearningPathData; rep: { id: string; name: string }; notes: HandoverNoteData[] }>();
</script>

<template>
  <OverviewPage :title="rep.name" :eyebrow="$t('learning_people.rep_path')">
    <p class="text-sm tabular-nums text-muted-foreground">{{ $t('learning_people.essentials') }} {{ path.totals.essentialDone }}/{{ path.totals.essentialTotal }} · {{ $t('learning_people.total') }} {{ path.totals.allDone }}/{{ path.totals.allTotal }}</p>
    <LearningPathView :path="path" readonly />
    <section v-if="notes.length" class="space-y-4" aria-labelledby="rep-handover">
      <h2 id="rep-handover" class="text-lg font-bold uppercase tracking-wide">{{ $t('learning_people.handover_notes') }}</h2>
      <HandoverNotes :notes="notes" />
    </section>
  </OverviewPage>
</template>
