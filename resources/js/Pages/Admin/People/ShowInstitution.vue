<template>
  <RecordPage
    v-model:section="currentSection"
    section-query-key="tab"
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
    <template v-if="fillActions.length" #next-actions>
      <RecordNextActions :fill="fillActions" />
    </template>

    <template v-if="hasDescription" #lead>
      <InstitutionAbout :description="institution.description" />
    </template>

    <template v-if="institution.is_active === false" #subtitle>
      <InactiveInstitutionBadge :active="institution.is_active" />
    </template>

    <template #fact-members>
      <UsersFactList :users="overview.current_users" :inline-limit="2" />
    </template>

    <template #fact-managers>
      <UsersFactList :users="institution.managers ?? []" :inline-limit="2" />
    </template>

    <template #overview>
      <InstitutionOverviewSection
        :institution
        :overview
        :duties="dutiesInOverview && (institution.duties_count > 0 || can.update) ? sortedDuties : null"
        :can-manage="can.update"
        :meetings-tab="tabs.some(tab => tab.value === 'meetings')"
        @navigate-tab="currentSection = $event"
        @assign="openAssignSheet"
        @edit-term="openTermSheet"
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
      <!-- Mounted once the section is in view, so the page never searches for a tab nobody opens. -->
      <div ref="meetingsAnchor">
        <InstitutionMeetingsCollection
          v-if="meetingsSeen"
          :key="institution.id"
          :institution-id="String(institution.id)"
          :can-record-meeting="can.recordMeeting"
          @record="openMeetingWindow"
        />
        <div v-else class="space-y-3" data-testid="meetings-skeleton">
          <div class="h-14 animate-pulse border-y border-border bg-secondary/60" />
          <div v-for="n in 3" :key="n" class="h-14 animate-pulse bg-secondary/40" />
        </div>
      </div>
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
        <InstitutionRelationsList
          :related="relatedInstitutions ?? []"
          :links="institutionLinks ?? []"
          :record-id="String(institution.id)"
          :record-name="institution.name"
          :can-manage="!!can.manageLinks"
          :kinds="relationKinds ?? []"
          data-testid="institution-relationships"
        />
      </WhenVisible>
    </template>

    <template #problems>
      <WhenVisible :key="institution.id" :buffer="0" :always="false" data="problems">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 2" :key="n" class="h-12 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <OverviewSection variant="home" :title="$t('Problemos')" :icon="getEntityTypeDefinition(ModelEnum.PROBLEM)?.icon" :count="problems?.length" data-testid="institution-problems">
          <template v-if="can.createProblem" #actions>
            <SectionHeaderAction :icon="Plus" :label="$t('Nauja problema')" :href="route('problems.create', { institution: institution.id })" />
          </template>
          <ProblemSummaryList :problems="problems ?? []" frame="attached" />
        </OverviewSection>
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

  <RelationshipLinkSheet
    v-if="linkSheetMounted"
    v-model:open="linkSheetOpen"
    subject="institution"
    :record-id="String(institution.id)"
    :record-name="institution.name"
    :link="null"
    :kinds="relationKinds ?? []"
  />

  <FileableUploadSheet
    v-if="uploadSheetMounted"
    v-model:open="uploadSheetOpen"
    :fileable="{ id: institution.id, type: 'Institution' }"
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
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onUnmounted, ref, watch } from 'vue';
import { useIntersectionObserver } from '@vueuse/core';
import { WhenVisible, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Calendar as CalendarIcon,
  CalendarRange,
  Clock,
  Edit3,
  ExternalLink,
  Eye,
  EyeOff,
  LayoutGrid,
  MailQuestion,
  Plus,
  UserCheck,
} from '@lucide/vue';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import InstitutionAbout from '@/Components/Institutions/InstitutionAbout.vue';
import InstitutionActivityRequestHistory from '@/Components/Institutions/InstitutionActivityRequestHistory.vue';
import { CadenceSection } from '@/Components/Cadences';
import InstitutionDutiesSection from '@/Components/Institutions/InstitutionDutiesSection.vue';
import InstitutionMeetingsCollection from '@/Components/Institutions/InstitutionMeetingsCollection.vue';
import InstitutionOverviewSection from '@/Components/Institutions/InstitutionOverviewSection.vue';
import { describeInstitutionActivity } from '@/Components/Institutions/institutionActivity';
import { SecretariesSection } from '@/Components/Institutions';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import UsersFactList from '@/Components/Avatars/UsersFactList.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { AdminRecordAction as ActionDescriptor } from '@/Types/AdminRecordAction';
import { OverviewSection, RecordNextActions, SectionHeaderAction, type RecordNextAction } from '@/Components/Patterns';
import ProblemSummaryList from '@/Components/Problems/ProblemSummaryList.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { enterInstitution } from '@/Composables/useRecordTrail';
import { getSuggestedCheckInRange, type TaskDisplayData } from '@/Composables/useTaskPresentation';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { inactiveInstitutionStatus, institutionActivityStatuses, type StatusPresentation } from '@/Constants/statusIcons';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { useDutyAssignmentSheet } from '@/Features/Admin/Occupancy/useDutyAssignmentSheet';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { useInstitutionSubscription } from '@/Composables/useInstitutionSubscription';
import { InstitutionScope, ModelEnum } from '@/Types/enums';
import type { InstitutionActivityStatus } from '@/Types/enums';
import type { ShowInstitutionPageProps } from '@/Types/ShowInstitutionPage';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<ShowInstitutionPageProps>();

watch(() => props.institution.id, () => enterInstitution(props.institution), { immediate: true });

const AssignDutyUserSheet = defineAsyncComponent(() => import('@/Features/Admin/Occupancy/AssignDutyUserSheet.vue'));
const FileableFilesPanel = defineAsyncComponent(() => import('@/Components/Files/FileableFilesPanel.vue'));
const FileableUploadSheet = defineAsyncComponent(() => import('@/Components/Files/FileableUploadSheet.vue'));
const RelationshipLinkSheet = defineAsyncComponent(() => import('@/Components/Relationships/RelationshipLinkSheet.vue'));
const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));
const InstitutionRelationsList = defineAsyncComponent(() => import('@/Components/Institutions/InstitutionRelationsList.vue'));

// --- Sections ---------------------------------------------------------------------------------

const currentSection = ref(new URL(usePage().url, 'http://localhost').searchParams.get('tab') ?? 'overview');

/** Up to three duties sit in the overview; more earn a tab of their own. */
const DUTIES_IN_OVERVIEW = 3;
const dutiesInOverview = computed(() => props.institution.duties_count <= DUTIES_IN_OVERVIEW);

const entityIcon = (type: ModelEnum) => getEntityTypeDefinition(type)?.icon;

const tabs = computed<RecordPageSection[]>(() => {
  const overview: RecordPageSection = { value: 'overview', label: $t('Santrauka'), icon: LayoutGrid };
  const duties: RecordPageSection[] = dutiesInOverview.value
    ? []
    : [{ value: 'duties', label: $t('Pareigybės'), count: props.institution.duties_count, icon: entityIcon(ModelEnum.DUTY) }];
  const meetings: RecordPageSection = { value: 'meetings', label: $t('Posėdžiai'), count: props.institution.meetings_count, icon: entityIcon(ModelEnum.MEETING) };
  const problems: RecordPageSection[] = (props.institution.problems_count ?? 0) > 0
    ? [{ value: 'problems', label: $t('Problemos'), count: props.institution.problems_count, icon: entityIcon(ModelEnum.PROBLEM) }]
    : [];
  const activityRequests: RecordPageSection[] = props.canViewActivityRequests
    ? [{ value: 'activityRequests', label: $t('activity_requests.history_title'), icon: MailQuestion }]
    : [];

  // A reader outside the institution gets its public face; meetings only where they are public.
  if (props.readOnly) {
    return [
      overview,
      ...duties,
      ...(props.institution.has_public_meetings ? [meetings] : []),
      ...problems,
      ...activityRequests,
    ];
  }

  // The work first, the configuration last.
  return [
    overview,
    ...duties,
    meetings,
    { value: 'tasks', label: $t('Užduotys'), count: props.incompleteTasksCount, icon: entityIcon(ModelEnum.TASK) },
    ...problems,
    // An empty Ryšiai or Failai tab gives way to an "add" action under the facts (see fillActions).
    ...(props.institution.related_institutions_count > 0
      ? [{ value: 'related', label: $t('Ryšiai'), count: props.institution.related_institutions_count, icon: entityIcon(ModelEnum.RELATIONSHIP) }]
      : []),
    ...(props.institution.has_files ? [{ value: 'files', label: $t('Failai'), icon: entityIcon(ModelEnum.FILE) }] : []),
    ...activityRequests,
    ...(props.can.update ? [{ value: 'terms', label: $t('Kadencijos ir sekretoriai'), icon: CalendarRange }] : []),
  ];
});

// --- Adding the first link or file: no empty tab, an action instead ------------------------------

const linkSheetOpen = ref(false);
const linkSheetMounted = ref(false);
const uploadSheetOpen = ref(false);
const uploadSheetMounted = ref(false);
/** The tab the first link or file will bring back, opened once the reload shows it. */
const revealAfterAdding = ref<'related' | 'files' | null>(null);

const fillActions = computed<RecordNextAction[]>(() => [
  ...(props.can.manageLinks && props.institution.related_institutions_count === 0
    ? [{
        key: 'add-link',
        label: $t('Pridėti ryšį'),
        icon: entityIcon(ModelEnum.RELATIONSHIP),
        run: () => {
          linkSheetMounted.value = true;
          linkSheetOpen.value = true;
          revealAfterAdding.value = 'related';
        },
      }]
    : []),
  ...(props.can.update && props.institution.sharepointPath && !props.institution.has_files
    ? [{
        key: 'add-file',
        label: $t('Pridėti failą'),
        icon: entityIcon(ModelEnum.FILE),
        run: () => {
          uploadSheetMounted.value = true;
          uploadSheetOpen.value = true;
          revealAfterAdding.value = 'files';
        },
      }]
    : []),
]);

// Closed without adding anything: nothing to reveal. A save closes the sheet after the reload, so the tab is back by then.
watch([linkSheetOpen, uploadSheetOpen], ([linkOpen, uploadOpen]) => {
  const target = revealAfterAdding.value;
  if (!linkOpen && !uploadOpen && target && !tabs.value.some(tab => tab.value === target)) {
    revealAfterAdding.value = null;
  }
});

watch(tabs, (current) => {
  const target = revealAfterAdding.value;
  if (target && current.some(tab => tab.value === target)) {
    currentSection.value = target;
    revealAfterAdding.value = null;
  }
});

// A tab remembered from a fuller visit may not exist here.
watch([tabs, currentSection], () => {
  if (!tabs.value.some(tab => tab.value === currentSection.value)) {
    currentSection.value = 'overview';
  }
}, { immediate: true });

const optionalProps = [
  'duties', 'tasks', 'problems', 'management', 'relatedInstitutions',
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

const hasDescription = computed(() => typeof props.institution.description === 'string' && props.institution.description.trim() !== '');

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
    // Whether its meetings are public is a property of the type, so it reads under it.
    detail: props.institution.has_public_meetings ? $t('Vieši posėdžiai') : $t('Nevieši posėdžiai'),
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

  if (props.institution.managers?.length) {
    facts.push({ key: 'managers', label: $t('Koordinatoriai') });
  }

  return facts;
});

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

const meetingsAnchor = ref<HTMLElement | null>(null);
const meetingsSeen = ref(false);
useIntersectionObserver(meetingsAnchor, ([entry]) => {
  if (entry?.isIntersecting) {
    meetingsSeen.value = true;
  }
});

// --- Duties tab and the Priskirti sheet (O21) -------------------------------------------------

const sortedDuties = computed(() =>
  [...(props.duties ?? [])].sort((a, b) => (a.order || 0) - (b.order || 0)));

const {
  open: assignSheetOpen,
  duty: sheetDuty,
  dutiable: sheetDutiable,
  user: sheetUser,
  takenIds,
  assign: openAssignSheet,
  editTerm: openTermSheet,
} = useDutyAssignmentSheet({
  terms: () => props.assignmentTerms,
  needsManagement: () => props.management === undefined,
});
</script>
