<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import type { LearningPathData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

const props = defineProps<{ path: LearningPathData; coordinators: LearningCoordinator[]; institutionContacts: LearningInstitutionContacts[] }>();
const percent = (done: number, total: number) => total === 0 ? 0 : Math.round(done / total * 100);
const figures = computed(() => [
  { key: 'essential', label: $t('learning.path.summary_essential'), done: props.path.totals.essentialDone, total: props.path.totals.essentialTotal, fill: 'bg-brand-fill' },
  { key: 'all', label: $t('learning.path.summary_all'), done: props.path.totals.allDone, total: props.path.totals.allTotal, fill: 'bg-foreground' },
]);
</script>

<template>
  <OverviewPage class="gap-6! lg:gap-8!" :title="$t('learning.pages.path')">
    <template #actions>
      <dl class="grid w-full grid-cols-2 gap-x-8 sm:w-auto" data-slot="learning-summary" :aria-label="$t('learning.path.summary')">
        <div v-for="figure in figures" :key="figure.key" class="flex min-w-0 flex-col sm:w-36">
          <dt class="order-2 mt-1.5 text-xs text-muted-foreground">{{ figure.label }}</dt>
          <dd class="u-display order-1 text-4xl leading-none tabular-nums">{{ figure.done }}<span class="text-muted-foreground">/{{ figure.total }}</span></dd>
          <dd class="order-3 mt-2 h-1.5 bg-muted" role="progressbar" :aria-valuenow="percent(figure.done, figure.total)" :aria-valuemin="0" :aria-valuemax="100" :aria-label="figure.label">
            <div :class="['h-full transition-[width]', figure.fill]" :style="{ width: `${percent(figure.done, figure.total)}%` }" />
          </dd>
        </div>
      </dl>
    </template>
    <LearningPathView :path="path" :coordinators="coordinators" :institution-contacts="institutionContacts" />
  </OverviewPage>
</template>
