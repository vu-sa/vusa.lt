<template>
  <OverviewPage
    :eyebrow="$t('shell.workspaces.organizacija.title')"
    :title="$t('organizacija.overview.title')"
    :head-title="`${$t('shell.workspaces.organizacija.title')} · ${$t('organizacija.overview.title')}`"
    :lead="$t('organizacija.overview.lead')"
  >
    <template v-if="canSeeDuties" #attention>
      <OverviewSection
        :title="$t('organizacija.overview.ending')"
        :empty="endingTerms.length === 0"
        :empty-text="$t('organizacija.overview.ending_empty')"
        :href="route('dutiables.timeline')"
        :href-label="$t('shell.sections.pareigybiu_laikotarpiai')"
      >
        <ul class="divide-y divide-border border-y border-border" data-slot="ending-terms">
          <li v-for="term in endingTerms" :key="term.id">
            <Link
              :href="route('duties.show', term.duty_id)"
              prefetch
              class="flex items-center gap-3 px-1 py-3 hover:bg-secondary pointer-coarse:py-4"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate font-medium">{{ term.duty }}</span>
                <span v-if="term.user" class="block truncate text-sm text-muted-foreground">{{ term.user }}</span>
              </span>
              <span v-if="term.ends_on" class="shrink-0 text-sm text-status-attention">
                {{ $t('organizacija.overview.ends_on', { date: formatNearDate(term.ends_on) }) }}
              </span>
            </Link>
          </li>
        </ul>
      </OverviewSection>
    </template>

    <OverviewNumbers v-if="numbers.length > 0" :numbers />

    <Deferred data="recentlyEdited">
      <template #fallback>
        <CollectionSkeleton :rows="2" />
      </template>
      <RecentlyEditedList :records="recentlyEdited ?? []" />
    </Deferred>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred, Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import type { HomeRecentRecord } from '@/Components/Home/types';
import RecentlyEditedList from '@/Components/Home/RecentlyEditedList.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import OverviewNumbers, { type OverviewNumberItem } from '@/Components/Overview/OverviewNumbers.vue';
import { CollectionSkeleton, OverviewSection } from '@/Components/Patterns';
import { formatNearDate } from '@/Utils/dateTime';

const props = defineProps<{
  counts: { endingSoon: number | null; emptyDuties: number | null; duties: number | null; members: number | null };
  endingTerms: { id: string; duty_id: string; duty: string; user: string | null; ends_on: string | null }[];
  recentlyEdited?: HomeRecentRecord[];
}>();

const canSeeDuties = computed(() => props.counts.duties !== null);

// A number the user may not open is null, so it is not drawn (hidden, never disabled).
const numbers = computed<OverviewNumberItem[]>(() => {
  const candidates: (OverviewNumberItem | null)[] = [
    props.counts.endingSoon === null
      ? null
      : {
          key: 'ending',
          label: $t('organizacija.overview.numbers.ending'),
          value: props.counts.endingSoon,
          href: route('dutiables.timeline'),
          tone: 'attention',
        },
    props.counts.emptyDuties === null
      ? null
      : {
          key: 'empty',
          label: $t('organizacija.overview.numbers.empty'),
          value: props.counts.emptyDuties,
          href: route('duties.index'),
          tone: 'attention',
        },
    props.counts.duties === null
      ? null
      : {
          key: 'duties',
          label: $t('organizacija.overview.numbers.duties'),
          value: props.counts.duties,
          href: route('duties.index'),
        },
    props.counts.members === null
      ? null
      : {
          key: 'members',
          label: $t('organizacija.overview.numbers.members'),
          value: props.counts.members,
          href: route('users.index'),
        },
  ];

  return candidates.filter((number): number is OverviewNumberItem => number !== null);
});
</script>
