<script setup lang="ts">
import { computed } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
import type { LearningPathData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

const props = defineProps<{ path: LearningPathData; coordinators: LearningCoordinator[]; institutionContacts: LearningInstitutionContacts[]; canSeeReps: boolean }>();

const percent = (done: number, total: number) => (total === 0 ? 0 : Math.round((done / total) * 100));
const essentialPercent = computed(() => percent(props.path.totals.essentialDone, props.path.totals.essentialTotal));
const allPercent = computed(() => percent(props.path.totals.allDone, props.path.totals.allTotal));
</script>

<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <OverviewPage class="gap-6! lg:gap-8! [&_[data-slot=overview-title-band]]:pt-4" :title="$t('learning.pages.path')">
      <section
        class="grid grid-cols-2 gap-x-6 gap-y-4 border-y border-border py-4 sm:grid-cols-[minmax(0,14rem)_minmax(0,14rem)_minmax(0,1fr)] sm:items-end sm:gap-10"
        data-slot="learning-summary"
        :aria-label="$t('learning.path.summary')"
      >
        <div>
          <p class="text-sm font-semibold text-foreground">{{ $t('learning.path.summary_essential') }}</p>
          <p class="u-display mt-1 text-3xl sm:text-4xl tabular-nums leading-none text-foreground">
            {{ path.totals.essentialDone }}<span class="text-muted-foreground">/{{ path.totals.essentialTotal }}</span>
          </p>
          <div class="mt-3 h-2 bg-muted" role="progressbar" :aria-valuenow="essentialPercent" aria-valuemin="0" aria-valuemax="100" :aria-label="$t('learning.path.summary_essential')">
            <div class="h-full bg-brand-fill" :style="{ width: `${essentialPercent}%` }" />
          </div>
        </div>
        <div>
          <p class="text-sm font-semibold text-foreground">{{ $t('learning.path.summary_all') }}</p>
          <p class="u-display mt-1 text-3xl sm:text-4xl tabular-nums leading-none text-muted-foreground">
            {{ path.totals.allDone }}<span>/{{ path.totals.allTotal }}</span>
          </p>
          <div class="mt-3 h-2 bg-muted" role="progressbar" :aria-valuenow="allPercent" aria-valuemin="0" aria-valuemax="100" :aria-label="$t('learning.path.summary_all')">
            <div class="h-full bg-foreground" :style="{ width: `${allPercent}%` }" />
          </div>
        </div>
        <div v-if="path.currentStage || coordinators.length" class="col-span-2 space-y-1 text-sm text-muted-foreground sm:col-span-1 sm:text-right">
          <p v-if="path.currentStage">
            {{ $t('learning.path.summary_now') }}
            <span class="font-semibold text-foreground">{{ $t(`learning.path.stages.${path.currentStage}`) }}</span>
          </p>
          <p v-if="coordinators.length">{{ $t('learning.transparency', { names: coordinators.map(person => person.name).join(', ') }) }}</p>
        </div>
      </section>

      <LearningPathView :path="path" :coordinators="coordinators" :institution-contacts="institutionContacts" />
    </OverviewPage>
  </LearningPageFrame>
</template>
