<script setup lang="ts">
import { computed } from 'vue';
import { ArrowUpRight, BookOpen, NotebookPen } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { NavigationTiles } from '@/Components/Patterns';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import type { LearningPathData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

const props = defineProps<{ path: LearningPathData; coordinators: LearningCoordinator[]; institutionContacts: LearningInstitutionContacts[] }>();
const percent = (done: number, total: number) => total === 0 ? 0 : Math.round(done / total * 100);
const figures = computed(() => [
  { key: 'essential', label: $t('learning.path.summary_essential'), done: props.path.totals.essentialDone, total: props.path.totals.essentialTotal, fill: 'bg-brand-fill' },
  { key: 'all', label: $t('learning.path.summary_all'), done: props.path.totals.allDone, total: props.path.totals.allTotal, fill: 'bg-foreground' },
]);
const tiles = computed(() => [
  { key: 'glossary', label: $t('learning.pages.glossary'), description: $t('learning.path.glossary_description'), href: route('glossaryTerms.index'), icon: BookOpen },
  { key: 'handover', label: $t('learning.path.handover_title'), description: $t('learning.path.handover_description'), href: route('learningPath.handover'), icon: NotebookPen },
]);
</script>

<template>
  <OverviewPage class="gap-6! lg:gap-8!" :title="$t('learning.pages.path')">
    <template #actions>
      <div class="grid grid-cols-2 gap-6" data-slot="learning-summary" :aria-label="$t('learning.path.summary')">
        <div v-for="figure in figures" :key="figure.key" class="min-w-28">
          <p class="text-xs text-muted-foreground">{{ figure.label }}</p>
          <p class="mt-1 text-lg font-bold tabular-nums">{{ figure.done }}<span class="font-normal text-muted-foreground">/{{ figure.total }}</span></p>
          <div class="mt-2 h-1 bg-muted" role="progressbar" :aria-valuenow="percent(figure.done, figure.total)" :aria-valuemin="0" :aria-valuemax="100" :aria-label="figure.label">
            <div :class="['h-full', figure.fill]" :style="{ width: `${percent(figure.done, figure.total)}%` }" />
          </div>
        </div>
      </div>
    </template>
    <LearningPathView :path="path" :coordinators="coordinators" :institution-contacts="institutionContacts">
      <template #before-meeting-prep>
        <NavigationTiles :items="tiles" :columns="2" :arrow-icon="ArrowUpRight" />
      </template>
    </LearningPathView>
  </OverviewPage>
</template>
