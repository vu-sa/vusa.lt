<template>
  <div>
    <PageTitleBand :eyebrow="goal.cadence ? `${$t('goals.public.term')} ${goal.cadence}` : undefined" :title="goal.title" :lead="goal.expected_result ?? undefined">
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <SectionBand divider="bottom" spacing="tight">
      <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
        <GoalStatusTag :status="goal.status" :label="goal.status_label" />
        <span v-if="goal.responsible_duty" class="text-muted-foreground">
          {{ $t('goals.public.responsible') }}: <span class="text-foreground">{{ goal.responsible_duty }}</span>
        </span>
      </div>

      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-if="goal.description" class="prose prose-zinc dark:prose-invert mt-8 max-w-3xl" v-html="goal.description" />
    </SectionBand>

    <SectionBand v-if="goal.evaluation" divider="bottom" spacing="tight">
      <EyebrowLabel as="h2" class="mb-4">
        {{ $t('goals.public.evaluation') }}
      </EyebrowLabel>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="prose prose-zinc dark:prose-invert max-w-3xl" v-html="goal.evaluation" />
    </SectionBand>

    <SectionBand spacing="tight">
      <EyebrowLabel as="h2" class="mb-6">
        {{ $t('goals.public.steps') }}
      </EyebrowLabel>

      <ol v-if="steps.length" class="max-w-3xl divide-y divide-border border-y border-border">
        <li v-for="step in steps" :key="step.id" class="flex gap-4 py-4 sm:gap-6">
          <time :datetime="step.happened_on" class="w-24 shrink-0 text-sm tabular-nums text-muted-foreground">
            {{ formatDate(step.happened_on) }}
          </time>
          <div class="min-w-0">
            <p class="font-medium text-foreground">
              {{ step.title }}
            </p>
            <p v-if="step.description" class="mt-1 whitespace-pre-line text-sm text-muted-foreground">
              {{ step.description }}
            </p>
            <ul v-if="step.meeting_url || step.document || step.url" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
              <li v-if="step.meeting_url">
                <SmartLink :href="step.meeting_url" class="inline-flex min-h-11 items-center gap-1 text-muted-foreground underline underline-offset-4 hover:text-brand sm:min-h-0">
                  {{ $t('goals.public.meeting') }}
                </SmartLink>
              </li>
              <li v-if="step.document">
                <a :href="step.document.url" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1 text-muted-foreground underline underline-offset-4 hover:text-brand sm:min-h-0">
                  {{ step.document.title }}
                </a>
              </li>
              <li v-if="step.url">
                <a :href="step.url" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1 text-muted-foreground underline underline-offset-4 hover:text-brand sm:min-h-0">
                  {{ $t('goals.public.link') }}
                </a>
              </li>
            </ul>
          </div>
        </li>
      </ol>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('goals.public.no_steps') }}
      </p>

      <SmartLink
        :href="route('publicGoals.index', { subdomain })"
        class="mt-8 inline-flex min-h-11 items-center gap-2 text-xs font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand"
      >
        <IFluentArrowLeft16Regular class="size-4" aria-hidden="true" />
        {{ $t('goals.public.all_goals') }}
      </SmartLink>
    </SectionBand>
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import EyebrowLabel from '@/Components/Brand/EyebrowLabel.vue';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import SectionBand from '@/Components/Public/Base/SectionBand.vue';
import GoalStatusTag from '@/Components/Public/Goals/GoalStatusTag.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type { GoalStatus } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';
import IFluentArrowLeft16Regular from '~icons/fluent/arrow-left-16-regular';

const props = defineProps<{
  goal: {
    id: string;
    title: string;
    expected_result: string | null;
    description: string | null;
    evaluation: string | null;
    status: GoalStatus;
    status_label: string;
    cadence: string | null;
    responsible_duty: string | null;
    url: string;
  };
  steps: {
    id: string;
    title: string;
    description: string | null;
    happened_on: string;
    url: string | null;
    meeting_url: string | null;
    document: { title: string; url: string } | null;
  }[];
}>();

const page = usePage();
const subdomain = computed(() => page.props.tenant?.subdomain ?? 'www');

usePageBreadcrumbs(() => BreadcrumbHelpers.publicContent([
  BreadcrumbHelpers.createBreadcrumbItem($t('goals.public.index_title'), route('publicGoals.index', { subdomain: subdomain.value })),
  BreadcrumbHelpers.createBreadcrumbItem(props.goal.title),
]), { placement: 'band' });
</script>
