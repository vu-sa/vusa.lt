<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
import type { LearningPathData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

defineProps<{ path: LearningPathData; coordinators: LearningCoordinator[]; institutionContacts: LearningInstitutionContacts[]; canSeeReps: boolean }>();
</script>

<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <OverviewPage :title="$t('learning.pages.path')">
      <div class="space-y-2">
        <p class="text-sm tabular-nums text-muted-foreground">{{ $t('learning.path.totals', { essentialDone: String(path.totals.essentialDone), essentialTotal: String(path.totals.essentialTotal), allDone: String(path.totals.allDone), allTotal: String(path.totals.allTotal) }) }}</p>
        <p v-if="coordinators.length" class="text-sm text-muted-foreground">{{ $t('learning.transparency', { names: coordinators.map(person => person.name).join(', ') }) }}</p>
      </div>
      <LearningPathView :path="path" :coordinators="coordinators" :institution-contacts="institutionContacts" />
    </OverviewPage>
  </LearningPageFrame>
</template>
