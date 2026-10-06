<template>
  <div>
    <PageTitleBand :title="$t('goals.public.index_title')" :lead="$t('goals.public.index_description')">
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <SectionBand v-for="group in groups" :key="group.cadence" divider="bottom" spacing="tight">
      <EyebrowLabel as="h2" class="mb-6">
        {{ group.cadence }}
      </EyebrowLabel>

      <HairlineList as="ul">
        <HairlineRow
          v-for="goal in group.goals"
          :key="goal.id"
          as="li"
          :title="goal.title"
          :meta="goal.expected_result ?? undefined"
          :href="goal.url"
        >
          <template #trailing>
            <GoalStatusTag :status="goal.status" :label="goal.status_label" />
          </template>
        </HairlineRow>
      </HairlineList>
    </SectionBand>

    <SectionBand v-if="goals.length === 0" spacing="tight">
      <p class="text-sm text-muted-foreground">
        {{ $t('goals.public.empty') }}
      </p>
    </SectionBand>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import EyebrowLabel from '@/Components/Brand/EyebrowLabel.vue';
import HairlineList from '@/Components/Public/Base/HairlineList.vue';
import HairlineRow from '@/Components/Public/Base/HairlineRow.vue';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import SectionBand from '@/Components/Public/Base/SectionBand.vue';
import GoalStatusTag from '@/Components/Public/Goals/GoalStatusTag.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type { GoalStatus } from '@/Types/enums';

interface PublicGoal {
  id: string;
  title: string;
  expected_result: string | null;
  status: GoalStatus;
  status_label: string;
  cadence: string | null;
  url: string;
  steps_count: number;
}

const props = defineProps<{ goals: PublicGoal[] }>();

// The server sorts newest term first; group while keeping that order.
const groups = computed(() => props.goals.reduce<{ cadence: string; goals: PublicGoal[] }[]>((acc, goal) => {
  const cadence = goal.cadence ?? '—';
  const group = acc.find(item => item.cadence === cadence);
  if (group) {
    group.goals.push(goal);
  }
  else {
    acc.push({ cadence, goals: [goal] });
  }
  return acc;
}, []));

usePageBreadcrumbs(() => BreadcrumbHelpers.publicContent([
  BreadcrumbHelpers.createBreadcrumbItem($t('goals.public.index_title')),
]), { placement: 'band' });
</script>
