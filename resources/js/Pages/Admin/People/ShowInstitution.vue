<template>
  <RecordPage
    v-model:section="currentSection"
    :history-subject="readOnly ? undefined : { type: 'institution', id: institution.id }"
    :title="institution.name"
    :entity-type="ModelEnum.INSTITUTION"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleRecordAction"
  >
    <template v-if="institution.is_active === false" #subtitle>
      <InactiveInstitutionBadge :active="institution.is_active" />
    </template>

    <template #fact-members>
      <UsersFactList :users="overview.current_users" :inline-limit="2" class="mt-1" />
    </template>

    <template #fact-managers>
      <UsersFactList :users="institution.managers ?? []" :inline-limit="2" class="mt-1" />
    </template>

    <template #overview>
      <InstitutionOverviewSection
        :institution
        :overview
        @navigate-tab="currentSection = $event"
        @view-meeting="(meeting) => router.visit(route('meetings.show', meeting.id))"
      />
    </template>

    <template v-if="canViewActivityRequests" #activityRequests>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="activityRequests">
        <template #fallback>
          <div class="h-32 animate-pulse border-y border-border bg-secondary" />
        </template>
        <InstitutionActivityRequestHistory v-if="activityRequests" :key="institution.id" :history="activityRequests" />
      </WhenVisible>
    </template>

    <template #duties>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="duties">
        <template #fallback>
          <div class="space-y-3" data-testid="duties-skeleton">
            <div v-for="n in 3" :key="n" class="h-14 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <InstitutionDutiesSection
          :duties="sortedDuties"
          :institution-id="institution.id"
          :can-manage="can.update"
          @assign="openAssignSheet"
          @edit-term="openTermSheet"
        />
      </WhenVisible>
    </template>

    <template #meetings>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="meetings">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 3" :key="n" class="h-12 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <InstitutionMeetingsList
          v-if="meetings?.length"
          :meetings
          :institution-name="institution.name"
          :can-delete="canDeleteMeetings"
          @select="(meeting) => router.visit(route('meetings.show', meeting.id))"
          @delete="askDeleteMeeting"
        />
        <EmptyState
          v-else
          :title="$t('Nėra susitikimų')"
          :description="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
          :icon="CalendarIcon"
          :action-label="can.recordMeeting ? $t('Suplanuoti susitikimą') : undefined"
          @action="openMeetingWindow"
        />
      </WhenVisible>
    </template>

    <template #terms>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="management">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 2" :key="n" class="h-16 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <div v-if="management" class="grid gap-10 xl:grid-cols-2 xl:gap-16" data-testid="institution-terms">
          <OverviewSection variant="home" :title="$t('secretaries.institution.title')" :icon="UserCheck">
            <SecretariesSection :institution-id="institution.id" :rosters="management.secretaryRosters" />
          </OverviewSection>

          <OverviewSection variant="home" :title="$t('cadences.institution.title')" :icon="CalendarRange">
            <CadenceSection
              :institution-id="institution.id"
              :own-cadences="management.cadences"
              :global-cadences="management.globalCadences"
              :defaults="management.cadenceDefaults"
              :reload-only="['management']"
            />
          </OverviewSection>
        </div>
      </WhenVisible>
    </template>

    <template #related>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" :data="['relatedInstitutions', 'institutionLinks']">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 3" :key="n" class="h-12 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <div class="grid gap-10 xl:grid-cols-2 xl:gap-16" data-testid="institution-relationships">
          <RelatedInstitutions :items="relatedInstitutions ?? []" />
          <RelationshipLinkList
            v-if="can.manageLinks || institutionLinks?.length"
            subject="institution"
            :record-id="institution.id"
            :record-name="institution.name"
            :links="institutionLinks ?? []"
            :can-manage="!!can.manageLinks"
            :kinds="relationKinds ?? []"
          />
        </div>
      </WhenVisible>
    </template>

    <template #problems>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="problems">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 2" :key="n" class="h-12 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <div class="space-y-4" data-testid="institution-problems">
          <div v-if="can.createProblem" class="flex justify-end">
            <Button as-child variant="outline" size="sm" voice="sentence">
              <Link :href="route('problems.create', { institution: institution.id })">
                <Plus aria-hidden="true" />
                {{ $t('Nauja problema') }}
              </Link>
            </Button>
          </div>
          <ProblemSummaryList :problems="problems ?? []" />
        </div>
      </WhenVisible>
    </template>

    <template #files>
      <WhenVisible :key="institution.id" :data="['files', 'typeFiles']" :buffer="0" :always="false">
        <template #fallback>
          <div class="space-y-3" data-testid="files-skeleton">
            <div v-for="n in 3" :key="n" class="h-12 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <FileableFilesPanel
          :fileable="{ id: institution.id, type: 'Institution' }"
          :files
          :type-files
          :can-upload="can.update && !!institution.sharepointPath"
          :folder-url="institution.sharepointFolderUrl"
          :can-delete="can.update"
        />
      </WhenVisible>
    </template>

    <template #tasks>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="tasks">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 2" :key="n" class="h-14 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <TaskManager
          :tasks="taskManagerTasks"
          :taskable="{ id: institution.id, type: ModelEnum.INSTITUTION }"
          @open-task-detail="openTaskDetail"
        />
      </WhenVisible>
    </template>

    <template v-if="!readOnly" #activity>
      <RecordActivity commentable-type="institution" :commentable-id="institution.id" />
    </template>
  </RecordPage>

  <AssignDutyUserSheet
    v-if="assignSheetOpen"
    v-model:open="assignSheetOpen"
    :duty="sheetDuty"
    :dutiable="sheetDutiable"
    :user="sheetUser"
    :study-programs="management?.studyPrograms ?? []"
    :taken-ids
  />

  <AddCheckInDialog
    v-if="showCheckInModal"
    :open="showCheckInModal"
    :institution-id="institution.id"
    :initial-start-date="checkInRange.start"
    :initial-end-date="checkInRange.end"
    :reload-props="['institution', 'overview', 'incompleteTasksCount']"
    @close="showCheckInModal = false"
  />

  <TaskDetailDialog
    v-if="selectedDetailTask"
    :open="showTaskDetail"
    :task="selectedDetailTask"
    @close="closeTaskDetail"
    @report="reportFromDetail"
  />

  <ConfirmDialog
    v-model:open="deleteMeetingOpen"
    :title="$t('Ištrinti posėdį?')"
    :description="meetingToDelete?.can_discard ? $t('Posėdis tuščias ir ką tik sukurtas, todėl bus ištrintas visam laikui.') : $t('Posėdis bus perkeltas į šiukšlinę.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="deleteMeeting"
  />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onUnmounted, ref, watch } from 'vue';
import { WhenVisible, Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Calendar as CalendarIcon,
  CalendarRange,
  Clock,
  Edit3,
  ExternalLink,
  Eye,
  EyeOff,
  MailQuestion,
  Plus,
  UserCheck,
} from '@lucide/vue';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import InstitutionActivityRequestHistory from '@/Components/Institutions/InstitutionActivityRequestHistory.vue';
import { CadenceSection } from '@/Components/Cadences';
import InstitutionDutiesSection from '@/Components/Institutions/InstitutionDutiesSection.vue';
import InstitutionMeetingsList from '@/Components/Institutions/InstitutionMeetingsList.vue';
import InstitutionOverviewSection from '@/Components/Institutions/InstitutionOverviewSection.vue';
import { describeInstitutionActivity } from '@/Components/Institutions/institutionActivity';
import { SecretariesSection } from '@/Components/Institutions';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import UsersFactList from '@/Components/Avatars/UsersFactList.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { AdminRecordAction as ActionDescriptor } from '@/Types/AdminRecordAction';
import { ConfirmDialog, EmptyState, OverviewSection } from '@/Components/Patterns';
import ProblemSummaryList from '@/Components/Problems/ProblemSummaryList.vue';
import { RelationshipLinkList } from '@/Components/Relationships';
import { Button } from '@/Components/ui/button';
import { useActionWindow } from '@/Composables/useActionWindow';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { enterInstitution } from '@/Composables/useRecordTrail';
import { getSuggestedCheckInRange, type TaskDisplayData } from '@/Composables/useTaskPresentation';
import { inactiveInstitutionStatus, institutionActivityStatuses, type StatusPresentation } from '@/Constants/statusIcons';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import type { AssignmentDuty, AssignmentTerm, AssignmentUser, DutyWithUsers, UserWithPivot } from '@/Types/DutyAssignment';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { useInstitutionSubscription } from '@/Composables/useInstitutionSubscription';
import { InstitutionScope, ModelEnum } from '@/Types/enums';
import type { ActionType, InstitutionActivityAnswer, InstitutionActivityCampaign, InstitutionActivityStatus, InstitutionRelationKind, MeetingType } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';

interface InstitutionUser {
  id: string;
  name: string;
  profile_photo_path: string | null;
  profile_photo_focal_point: string | null;
}

interface InstitutionMemberTerm {
  duty_id: string;
  dutiable_id: string;
  dutiable_type: string;
  id: string;
  via_dutiable_id: string | null;
  tenant_id: number | null;
  start_date: string;
  end_date: string | null;
  additional_photo: string | null;
  additional_photo_focal_point: string | null;
  additional_email: string | null;
  use_original_duty_name: boolean;
  description: { lt: string; en: string } | null;
  study_program_id: string | null;
  study_program_note: { lt: string; en: string } | null;
  created_at: string;
  updated_at: string;
}

interface InstitutionMember extends InstitutionUser {
  pivot: Pick<InstitutionMemberTerm, 'id' | 'start_date' | 'end_date'>;
}

interface InstitutionPageDuty {
  id: string;
  name: string | null;
  description: string | null;
  institution_id: string;
  order: number;
  email: string | null;
  contacts_grouping: string;
  places_to_occupy: number | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  current_users: InstitutionMember[];
}

interface InstitutionPageType {
  id: number;
  parent_id: number | null;
  title: string | null;
  description: string | null;
  slug: string | null;
  extra_attributes: Record<string, unknown> | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  pivot: { institution_id: string; institution_type_id: number };
}

interface InstitutionPageData {
  id: string;
  name: string;
  is_active: boolean;
  description: string | null;
  tenant: { id: number; shortname: string } | null;
  types: InstitutionPageType[];
  has_public_meetings: boolean;
  governance_scope: InstitutionScope;
  duties_count: number;
  meetings_count: number;
  problems_count: number;
  related_institutions_count: number;
  managers: InstitutionMember[];
  secretaries: InstitutionSecretary[];
  sharepointPath: string | null;
  sharepointFolderUrl: string | null;
}

interface InstitutionPageMeeting {
  id: string;
  title: string;
  start_time: string;
  type: MeetingType | null;
  agenda_items_count: number;
  vote_matches: number;
  vote_mismatches: number;
  incomplete_vote_data: number;
  has_protocol: boolean;
  has_report: boolean;
  can_discard: boolean;
}

interface InstitutionActivityData {
  status: InstitutionActivityStatus;
  requires_action: boolean;
  priority: number;
  periodicity_days: number;
  effective_days_since_activity: number | null;
  progress_percentage: number | null;
  last_activity_type: 'meeting' | 'check_in' | null;
  last_activity_at: string | null;
  last_meeting_at: string | null;
  next_meeting_at: string | null;
  active_check_in_until: string | null;
  last_completed_check_in_at: string | null;
}

interface InstitutionOverviewData {
  activity_status: InstitutionActivityData | null;
  current_users: InstitutionMember[];
  recentMeetings: InstitutionPageMeeting[];
  meetings_count: number;
  meetings_hidden: boolean;
}

interface InstitutionProblem {
  id: string;
  title: string;
  status: string;
  occurred_at: string;
  resolved_at: string | null;
  tenant: { id: number; shortname: string } | null;
  responsible_user: { id: string; name: string } | null;
}

interface InstitutionPageRelatedInstitution {
  id: string;
  name: string | null;
  is_active: boolean;
  direction: 'outgoing' | 'incoming' | 'mutual';
  via: 'direct' | 'type';
  kind: InstitutionRelationKind;
  kind_label: string;
  cross_tenant: boolean;
  authorized: boolean;
}

interface InstitutionLinkRow {
  id: number;
  created_at: string | null;
  updated_at: string | null;
  other: { id: string; name: string | null; is_active: boolean };
  direction: 'outgoing' | 'incoming';
  kind: InstitutionRelationKind;
  kind_label: string;
  mutual: boolean;
}

interface InstitutionManagement {
  cadences: InstitutionCadence[];
  globalCadences: InstitutionCadence[];
  cadenceDefaults: { default_start_month_day: string; default_end_month_day: string };
  secretaryRosters: InstitutionSecretaryRoster[];
  studyPrograms: InstitutionStudyProgram[];
}

interface InstitutionTask {
  id: string;
  name: string;
  description: string | null;
  due_date: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  created_at: string;
  updated_at: string;
  action_type: ActionType | null;
  metadata: Record<string, unknown> | null;
  progress: { current: number; total: number; percentage: number } | null;
  is_overdue: boolean;
  can_be_manually_completed: boolean;
  can_update: boolean;
  icon: string;
  color: string;
  taskable: { id: number | string; name: string | null; type: string } | null;
  taskable_type: string;
  taskable_id: string;
  users: ({ id: string; name: string; profile_photo_path: string | null })[];
}

interface InstitutionHistoryItem {
  id: string;
  campaign_type: InstitutionActivityCampaign;
  requester: string | null;
  automatic: boolean;
  recipient: string | null;
  period_start: string;
  period_end: string | null;
  legacy: boolean;
  note: string | null;
  answer: InstitutionActivityAnswer | null;
  created_at: string | null;
  answered_at: string | null;
  resolved_at: string | null;
  expires_at: string;
  resolution_source: string | null;
  resolved_by: string | null;
  status: 'pending' | 'answered' | 'resolved' | 'expired';
  meetings: { id: string; date: string; url: string | null }[];
  check_ins: { id: string; start: string; end: string }[];
}

interface InstitutionHistory {
  data: { id: string; campaigns: Record<string, InstitutionHistoryItem[]> }[];
  next_page: number | null;
}

interface InstitutionCadence {
  id: string;
  institution_id: string | null;
  institution_name: string | null;
  start_date: string;
  end_date: string;
  start_meeting: { id: string; title: string; start_time: string; institution_id: string | null; institution_name: string | null } | null;
  end_meeting: { id: string; title: string; start_time: string; institution_id: string | null; institution_name: string | null } | null;
  label: string;
}

interface InstitutionSecretaryRoster {
  cadence_id: string;
  label: string;
  start_date: string;
  end_date: string;
  is_global: boolean;
  is_current: boolean;
  is_past: boolean;
  secretaries: InstitutionSecretary[];
}

interface InstitutionSecretary {
  id: string;
  name: string;
  email: string | null;
  profile_photo_path: string | null;
}

interface InstitutionFile {
  id: string;
  fileable_type: string;
  fileable_id: string;
  sharepoint_id: string;
  sharepoint_path: string | null;
  name: string;
  file_type: string | null;
  mime_type: string | null;
  size_bytes: number | null;
  file_date: string | null;
  description: string | null;
  public_link: string | null;
  public_link_expires_at: string | null;
  last_synced_at: string | null;
  deleted_externally_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  formatted_size: string | null;
}

interface InstitutionStudyProgram {
  id: string;
  name: string;
  degree: string;
  tenant_id: number;
}

const props = defineProps<{
  institution: InstitutionPageData;
  overview: InstitutionOverviewData;
  activityRequests?: InstitutionHistory;
  canViewActivityRequests: boolean;
  can: { update: boolean; delete: boolean; recordMeeting: boolean; reportActivity: boolean; askAboutActivity: boolean; createProblem: boolean; manageLinks: boolean };
  duties?: InstitutionPageDuty[];
  meetings?: InstitutionPageMeeting[];
  problems?: InstitutionProblem[];
  tasks?: InstitutionTask[];
  incompleteTasksCount: number;
  assignmentTerms?: InstitutionMemberTerm[];
  relatedInstitutions?: InstitutionPageRelatedInstitution[];
  institutionLinks?: InstitutionLinkRow[];
  relationKinds: { value: InstitutionRelationKind; label: string }[];
  files?: InstitutionFile[];
  typeFiles?: (InstitutionFile & { fileable: { id: number; title: string | null } | null })[];
  management?: InstitutionManagement | null;
  subscription: { is_followed: boolean; is_duty_based: boolean } | null;
  readOnly: boolean;
}>();

watch(() => props.institution.id, () => enterInstitution(props.institution), { immediate: true });

const AssignDutyUserSheet = defineAsyncComponent(() => import('@/Features/Admin/Occupancy/AssignDutyUserSheet.vue'));
const FileableFilesPanel = defineAsyncComponent(() => import('@/Components/Files/FileableFilesPanel.vue'));
const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));
const RelatedInstitutions = defineAsyncComponent(() => import('@/Components/Carousels/RelatedInstitutions.vue'));

// --- Sections ---------------------------------------------------------------------------------

const currentSection = ref(new URL(usePage().url, 'http://localhost').searchParams.get('tab') ?? 'overview');

const problemsTab = computed<RecordPageSection[]>(() => (props.institution.problems_count ?? 0) > 0
  ? [{ value: 'problems', label: $t('Problemos'), count: props.institution.problems_count }]
  : []);

/** Dropped rather than disabled when there is nothing to show — a tab that cannot be opened is worse than none. */
const tabs = computed<RecordPageSection[]>(() => {
  // A reader outside the institution gets its public face; meetings only where they are public.
  if (props.readOnly) {
    return [
      { value: 'overview', label: $t('Apžvalga') },
      { value: 'duties', label: $t('Pareigybės'), count: props.institution.duties_count },
      ...(props.institution.has_public_meetings
        ? [{ value: 'meetings', label: $t('Posėdžiai'), count: props.institution.meetings_count }]
        : []),
      ...(props.canViewActivityRequests ? [{ value: 'activityRequests', label: $t('activity_requests.history_title') }] : []),
      ...problemsTab.value,
    ];
  }

  return [
    { value: 'overview', label: $t('Apžvalga') },
    { value: 'duties', label: $t('Pareigybės'), count: props.institution.duties_count },
    { value: 'meetings', label: $t('Posėdžiai'), count: props.institution.meetings_count },
    ...(props.can.update ? [{ value: 'terms', label: $t('Kadencijos ir sekretoriai') }] : []),
    ...(props.institution.related_institutions_count > 0 || props.can.manageLinks
      ? [{ value: 'related', label: $t('Ryšiai'), count: props.institution.related_institutions_count }]
      : []),
    ...problemsTab.value,
    { value: 'files', label: $t('Failai') },
    ...(props.canViewActivityRequests ? [{ value: 'activityRequests', label: $t('activity_requests.history_title') }] : []),
    { value: 'tasks', label: $t('Užduotys'), count: props.incompleteTasksCount },
  ];
});

// A tab remembered from a fuller visit may not exist here.
watch([tabs, currentSection], () => {
  if (!tabs.value.some(tab => tab.value === currentSection.value)) {
    currentSection.value = 'overview';
  }
}, { immediate: true });

const optionalProps = [
  'duties', 'meetings', 'tasks', 'problems', 'management', 'relatedInstitutions',
  'institutionLinks', 'files', 'typeFiles', 'activityRequests', 'assignmentTerms',
] as const;

const stopPreservingPanels = router.on('before', ({ detail: { visit } }) => {
  if (visit.method === 'get' || visit.only.length) return;

  visit.except = [...new Set([...visit.except, ...optionalProps.filter(key => props[key] === undefined)])];
  if (!visit.except.length) {
    visit.only = Object.keys(usePage().props);
  }
});
onUnmounted(stopPreservingPanels);

watch(() => props.institution.id, () => {
  currentSection.value = new URL(usePage().url, 'http://localhost').searchParams.get('tab') ?? 'overview';
});

// --- Title band -------------------------------------------------------------------------------

const primaryType = computed(() => {
  const type = props.institution.types?.[0];

  return typeof type?.title === 'string' ? type.title : null;
});

// Withheld with the meetings when they are not public and the reader has no access.
const activityStatus = computed<StatusPresentation>(() => props.institution.is_active === false
  ? inactiveInstitutionStatus
  : props.overview.activity_status
    ? institutionActivityStatuses[props.overview.activity_status.status as InstitutionActivityStatus]
    : { label: 'Nerodoma', role: 'neutral', icon: EyeOff });

const filledPositions = computed(() => props.overview.current_users.length);

const recordFacts = computed<RecordFact[]>(() => {
  // The meeting status leads, toned in its colour, with what it rests on — the same line the
  // action window's institution picker shows.
  const facts: RecordFact[] = [{
    key: 'status',
    label: $t('visak.institution_summary.status'),
    status: activityStatus.value,
    detail: props.overview.activity_status
      ? describeInstitutionActivity(props.overview.activity_status, {
          day: value => formatDate(value, { format: 'short' }),
          fullDay: value => formatDate(value, { format: 'full' }),
        })
      : null,
  }];

  const scope = props.institution.governance_scope;
  const isVusa = scope === InstitutionScope.Vusa;
  facts.push({
    key: 'type',
    label: scope ? $t(`forms.options.governance_scope_${scope}`) : $t('Institucijos tipas'),
    value: primaryType.value ?? '—',
    surfaceClass: scope
      ? (isVusa ? 'bg-brand/5' : 'bg-[#78003F]/10 dark:bg-[#78003F]/25')
      : undefined,
    labelClass: scope
      ? (isVusa ? 'text-brand' : 'text-[#78003F] dark:text-[#d99fbd]')
      : undefined,
  });

  if (props.institution.tenant?.shortname) {
    facts.push({ key: 'tenant', label: $t('Padalinys'), value: props.institution.tenant.shortname });
  }

  facts.push({
    key: 'members',
    label: `${$t('Nariai')} · ${filledPositions.value}`,
  });

  facts.push({
    key: 'visibility',
    label: $t('Posėdžių viešumas'),
    value: props.institution.has_public_meetings ? $t('Vieši posėdžiai') : $t('Nevieši posėdžiai'),
  });

  if (props.institution.managers?.length) {
    facts.push({ key: 'managers', label: $t('Koordinatoriai') });
  }

  return facts;
});

// --- Permissions ------------------------------------------------------------------------------

const permissions = computed(() => usePage().props.auth?.can as Record<string, boolean> | undefined);
const canDeleteMeetings = computed(() => permissions.value?.['meetings.delete.padalinys'] ?? false);

// --- Subscription (Sekti / Nebesekti) ---------------------------------------------------------

const isFollowed = ref(props.subscription?.is_followed ?? false);
const isDutyBased = computed(() => props.subscription?.is_duty_based ?? false);

const subscriptionState = computed(() => ({
  is_followed: isFollowed.value,
  is_duty_based: isDutyBased.value,
}));

const { toggleFollow: doToggleFollow } = useInstitutionSubscription();

const toggleFollow = async () => {
  if (isDutyBased.value) {
    return;
  }

  const next = await doToggleFollow(String(props.institution.id), subscriptionState.value, ['subscription']);
  isFollowed.value = next;
};

// One primary action; everything else lives in ⋯ (.ai/rules/js-pages-admin.md).
const primaryAction = computed<ActionDescriptor | undefined>(() =>
  props.can.recordMeeting || props.can.reportActivity
    ? { key: 'activity', label: $t('Fiksuoti veiklą'), icon: CalendarIcon }
    : undefined);

/** By id, so it resolves whatever the institution's alias is (same as the old form's status link). */
const publicUrl = computed(() => route('contacts.institution', {
  institution: props.institution.id,
  subdomain: resolveTenantSubdomain(props.institution.tenant?.id ?? undefined),
  lang: usePage().props.app?.locale || 'lt',
}));

const overflowActions = computed<ActionDescriptor[]>(() => {
  const actions: ActionDescriptor[] = [];

  if (props.can.update) {
    actions.push({ key: 'edit', label: $t('Redaguoti instituciją'), icon: Edit3 });
  }

  if (props.can.reportActivity) {
    actions.push({ key: 'check-in', label: $t('Pridėti pažymą'), icon: Clock });
  }

  if (props.can.askAboutActivity) {
    actions.push({ key: 'ask-activity', label: $t('shell.actions.ask_activity.title'), icon: MailQuestion });
  }

  if (!props.readOnly) {
    actions.push({ key: 'timeline', label: $t('dutiables.timeline.open'), icon: CalendarRange });
  }
  actions.push({ key: 'public', label: $t('Atidaryti vusa.lt'), icon: ExternalLink, href: publicUrl.value, external: true });

  if (props.subscription) {
    actions.push({
      key: 'follow',
      label: isFollowed.value ? $t('Nebesekti') : $t('Sekti'),
      icon: isFollowed.value ? EyeOff : Eye,
    });
  }

  return actions;
});

const handleRecordAction = (key: string) => {
  switch (key) {
    case 'activity':
      openActivityWindow();
      break;
    case 'edit':
      router.visit(route('institutions.edit', props.institution.id));
      break;
    case 'check-in':
      openCheckInModal();
      break;
    case 'ask-activity':
      actionWindow.open({ flow: 'activity.request', institutions: [{ id: props.institution.id, name: props.institution.name }], lockActivityRequestInstitutions: true });
      break;
    case 'timeline':
      router.visit(route('dutiables.timeline', { institution: props.institution.id }));
      break;
    case 'follow':
      void toggleFollow();
      break;
  }
};

// --- Meeting window, check-ins and tasks ------------------------------------------------------

const actionWindow = useActionWindow();

const openMeetingWindow = () => actionWindow.open({
  flow: 'meeting.create',
  institution: {
    id: props.institution.id,
    name: props.institution.name,
    isInternal: props.institution.governance_scope === InstitutionScope.Vusa,
  },
});

const openActivityWindow = () => actionWindow.open({
  flow: 'institution.report',
  institution: {
    id: props.institution.id,
    name: props.institution.name,
    isInternal: props.institution.governance_scope === InstitutionScope.Vusa,
  },
});

const showCheckInModal = ref(false);
const checkInRange = computed(() => getSuggestedCheckInRange(null));

const openCheckInModal = () => {
  showCheckInModal.value = true;
};

const taskManagerTasks = computed(
  () => props.tasks ?? [],
);

const showTaskDetail = ref(false);
const selectedDetailTask = ref<TaskDisplayData | null>(null);

const openTaskDetail = (task: TaskDisplayData) => {
  selectedDetailTask.value = task;
  showTaskDetail.value = true;
};

const closeTaskDetail = () => {
  showTaskDetail.value = false;
  selectedDetailTask.value = null;
};

// A periodicity task is always about this institution: record a meeting, or say there was none.
const reportFromDetail = () => {
  closeTaskDetail();
  openActivityWindow();
};

// --- Meetings tab -----------------------------------------------------------------------------

const deleteMeetingOpen = ref(false);
const meetingToDelete = ref<{ id: string; can_discard?: boolean } | null>(null);

const askDeleteMeeting = (meeting: { id: string; can_discard?: boolean }) => {
  meetingToDelete.value = meeting;
  deleteMeetingOpen.value = true;
};

const deleteMeeting = () => {
  if (meetingToDelete.value) {
    router.delete(route('meetings.destroy', meetingToDelete.value.id), { preserveScroll: true });
  }
};

// --- Duties tab and the Priskirti sheet (O21) -------------------------------------------------

const sortedDuties = computed(() =>
  [...(props.duties ?? [])].sort((a, b) => (a.order || 0) - (b.order || 0)));

const assignSheetOpen = ref(false);
const sheetDuty = ref<(AssignmentDuty & { current_users?: UserWithPivot[] }) | null>(null);
const sheetDutiable = ref<AssignmentTerm | null>(null);
const sheetUser = ref<AssignmentUser | null>(null);

/** Who already holds the duty right now cannot be assigned to it again. */
const holdsNow = (duty: { current_users?: UserWithPivot[] } | null) => (duty?.current_users ?? []);
const takenIds = computed(() => holdsNow(sheetDuty.value).map(user => String(user.id)));

const openAssignment = (duty: DutyWithUsers, user: UserWithPivot | null) => {
  const open = () => {
    const term = user ? props.assignmentTerms?.find(term => term.id === user.pivot?.id) : null;
    if (user && !term) return;

    sheetDuty.value = duty;
    sheetDutiable.value = term ?? null;
    sheetUser.value = user;
    assignSheetOpen.value = true;
  };
  const only = [
    ...(props.management === undefined ? ['management'] : []),
    ...(user ? ['assignmentTerms'] : []),
  ];
  if (only.length) {
    router.reload({ only, onSuccess: open });
  } else {
    open();
  }
};

const openAssignSheet = (duty: DutyWithUsers) => openAssignment(duty, null);
const openTermSheet = (duty: DutyWithUsers, user: UserWithPivot) => openAssignment(duty, user);
</script>
