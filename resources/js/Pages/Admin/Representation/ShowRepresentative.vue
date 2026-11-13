<template>
  <RecordPage
    v-model:section="currentSection"
    section-query-key="tab"
    :title="rep.name"
    :entity-type="ModelEnum.USER"
    :eyebrow-suffix="$t('representatives.rep.eyebrow')"
    :facts
    :sections
    :primary-action
    :overflow-actions
    @action="onAction"
  >
    <template #identity>
      <UserAvatar :user="{ name: rep.name, profile_photo_path: rep.photo }" size="xl" border class="size-12 shrink-0 sm:size-14" />
    </template>

    <template #subtitle>
      <span class="text-sm text-muted-foreground">{{ institutionNames(rep) }}</span>
    </template>

    <template v-if="rep.attention" #alert>
      <p
        class="border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] px-4 py-3 text-sm font-medium text-foreground"
        data-slot="representative-attention-reason"
      >
        {{ attentionText(rep) }}
      </p>
    </template>

    <template #overview>
      <OverviewSection :title="$t('representatives.rep.institutions')" :icon="Landmark" variant="home" :count="rep.institutions.length">
        <ul class="divide-y divide-border/60">
          <li v-for="institution in rep.institutions" :key="institution.id" class="flex flex-wrap items-center gap-x-4 gap-y-2 py-4" :data-institution="institution.id">
            <span class="min-w-0 flex-1 basis-48">
              <Link :href="route('institutions.show', institution.id)" class="block text-pretty font-bold hover:text-brand">
                {{ institution.name }}
              </Link>
              <span class="block text-xs text-muted-foreground">
                {{ meetingLine(institution) }}
              </span>
            </span>
            <StatusBadge :status="institutionActivityStatuses[institution.status]" />
          </li>
        </ul>
      </OverviewSection>
    </template>

    <template v-if="path" #path>
      <p class="mb-6 text-sm tabular-nums text-muted-foreground">
        {{ $t('representatives.rep.path_progress', {
          essentialDone: String(path.totals.essentialDone),
          essentialTotal: String(path.totals.essentialTotal),
          allDone: String(path.totals.allDone),
          allTotal: String(path.totals.allTotal),
        }) }}
      </p>
      <LearningPathView :path readonly />
    </template>
  </RecordPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Landmark, Mail, Route, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { OverviewSection, StatusBadge } from '@/Components/Patterns';
import { institutionActivityStatuses } from '@/Constants/statusIcons';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import { attentionText, institutionNames } from '@/Features/Admin/Representatives/representativeText';
import type { RepInstitution, RepRow } from '@/Features/Admin/Representatives/types';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { ModelEnum } from '@/Types/enums';
import type { LearningPathData } from '@/Types/learning';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  rep: RepRow;
  /** Only for the rep's coordinator: progress never follows padalinys rights. */
  path: LearningPathData | null;
  tasks: { tasks_count: number; overdue_tasks_count: number; due_soon_tasks_count: number };
  canViewMember: boolean;
}>();

const currentSection = ref('overview');

const sections = computed<RecordPageSection[]>(() => [
  { value: 'overview', label: $t('representatives.rep.overview') },
  ...(props.path ? [{ value: 'path', label: $t('representatives.rep.path'), icon: Route }] : []),
]);

const facts = computed<RecordFact[]>(() => {
  const items: RecordFact[] = [];
  if (props.rep.termStart) {
    items.push({ key: 'term', label: $t('representatives.rep.term'), value: formatDate(props.rep.termStart) });
  }
  items.push({
    key: 'last_login',
    label: $t('representatives.rep.last_login'),
    value: $t(`representatives.activity.${props.rep.activity}`),
  });
  items.push({
    key: 'meetings',
    label: $t('representatives.rep.meetings'),
    value: $t('representatives.rep.meetings_value', { recorded: String(props.rep.meetings.recorded), held: String(props.rep.meetings.held) }),
    detail: $t('representatives.rep.meetings_detail'),
  });
  if (props.rep.learning && props.rep.learning.essentialTotal > 0) {
    items.push({ key: 'essentials', label: $t('representatives.rep.essentials'), value: `${props.rep.learning.essentialDone} / ${props.rep.learning.essentialTotal}` });
  }
  items.push({
    key: 'tasks',
    label: $t('representatives.rep.tasks'),
    value: props.tasks.tasks_count,
    detail: props.tasks.overdue_tasks_count > 0 ? $t('representatives.rep.tasks_overdue', { count: String(props.tasks.overdue_tasks_count) }) : undefined,
  });

  return items;
});

const primaryAction = computed<AdminRecordAction>(() => ({
  key: 'write', label: $t('representatives.rep.write'), icon: Mail,
}));

// A mailto is not an Inertia visit, so the record action resolves it here.
function onAction(key: string): void {
  if (key === 'write') window.location.href = `mailto:${props.rep.email}`;
}

const overflowActions = computed<AdminRecordAction[]>(() => props.canViewMember
  ? [{ key: 'member', label: $t('representatives.rep.open_member'), icon: UserRound, href: route('users.show', props.rep.id) }]
  : []);

function meetingLine(institution: RepInstitution): string {
  const parts = [
    institution.tenant,
    institution.lastMeetingAt ? $t('representatives.rep.last_meeting', { date: formatDate(institution.lastMeetingAt) }) : null,
    institution.nextMeetingAt ? $t('representatives.rep.next_meeting', { date: formatDate(institution.nextMeetingAt) }) : null,
  ].filter(Boolean);

  return parts.length > (institution.tenant ? 1 : 0) ? parts.join(' · ') : [institution.tenant, $t('representatives.rep.no_meetings')].filter(Boolean).join(' · ');
}
</script>
