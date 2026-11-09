<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import type { LearningPathData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

defineProps<{ path: LearningPathData; coordinators: LearningCoordinator[]; institutionContacts: LearningInstitutionContacts[]; canSeeReps: boolean }>();
</script>

<template>
  <OverviewPage :title="$t('learning.pages.path')">
    <template #hero>
      <LearningTabs :can-see-reps="canSeeReps" />
      <h1 class="u-display mt-6 text-4xl text-foreground sm:text-5xl">{{ $t('learning.pages.path') }}</h1>
      <p class="mt-3 text-sm tabular-nums text-muted-foreground">{{ $t('learning.path.totals', { essentialDone: String(path.totals.essentialDone), essentialTotal: String(path.totals.essentialTotal), allDone: String(path.totals.allDone), allTotal: String(path.totals.allTotal) }) }}</p>
      <p v-if="coordinators.length" class="mt-2 text-sm text-muted-foreground">{{ $t('learning.transparency', { names: coordinators.map(person => person.name).join(', ') }) }}</p>
    </template>
    <LearningPathView :path="path" :coordinators="coordinators" :institution-contacts="institutionContacts" />
  </OverviewPage>
</template>
