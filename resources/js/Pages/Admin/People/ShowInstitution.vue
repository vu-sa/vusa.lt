<template>
  <RecordPage
    v-model:section="currentSection"
    :title="institution.name"
    :entity-type="ModelEnum.INSTITUTION"
    :status="activityBadge"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    @action="handleRecordAction"
  >
    <template #subtitle>
      <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
        <span v-if="primaryType">{{ primaryType }}</span>
        <InstitutionScopeBadge v-if="institution.governance_scope" :scope="institution.governance_scope" />
        <span v-if="institution.has_public_meetings" class="inline-flex items-center gap-1">
          <Globe class="size-3.5" aria-hidden="true" />
          {{ $t('Vieši posėdžiai') }}
        </span>
      </div>
    </template>

    <template #overview>
      <InstitutionOverviewSection
        :institution
        :overview
        @navigate-tab="currentSection = $event"
        @schedule-meeting="openMeetingWindow"
        @report-activity="openCheckInModal"
        @view-meeting="(meeting) => router.visit(route('meetings.show', meeting.id))"
      />
    </template>

    <template #duties>
      <Deferred data="duties">
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
      </Deferred>
    </template>

    <template #meetings>
      <Deferred data="meetings">
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
          :action-label="canScheduleMeeting ? $t('Suplanuoti susitikimą') : undefined"
          @action="openMeetingWindow"
        />
      </Deferred>
    </template>

    <template #terms>
      <Deferred data="management">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 2" :key="n" class="h-16 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <div v-if="management" class="space-y-10" data-testid="institution-terms">
          <section class="space-y-3">
            <div class="border-b border-border pb-2">
              <h3 class="text-base font-semibold text-foreground">
                {{ $t('cadences.institution.title') }}
              </h3>
              <p class="mt-0.5 text-xs text-muted-foreground">
                {{ $t('cadences.institution.description') }}
              </p>
            </div>
            <CadenceSection
              :institution-id="institution.id"
              :own-cadences="management.cadences"
              :global-cadences="management.globalCadences"
              :defaults="management.cadenceDefaults"
            />
          </section>

          <section class="space-y-3">
            <div class="border-b border-border pb-2">
              <h3 class="text-base font-semibold text-foreground">
                {{ $t('secretaries.institution.title') }}
              </h3>
              <p class="mt-0.5 text-xs text-muted-foreground">
                {{ $t('secretaries.institution.description') }}
              </p>
            </div>
            <SpotlightPopover
              :title="$t('secretaries.spotlight.title')"
              :description="$t('secretaries.spotlight.description')"
              :is-dismissed="!secretariesSpotlight.isVisible.value"
              position="top"
              @dismiss="secretariesSpotlight.dismiss"
            >
              <SecretariesSection
                :institution-id="institution.id"
                :rosters="management.secretaryRosters"
                :suggested="management.suggestedSecretaries"
                @engaged="secretariesSpotlight.dismiss"
              />
            </SpotlightPopover>
          </section>
        </div>
      </Deferred>
    </template>

    <template #related>
      <Deferred data="relatedInstitutions">
        <template #fallback>
          <div class="space-y-3">
            <div v-for="n in 3" :key="n" class="h-12 animate-pulse border border-border bg-secondary/60" />
          </div>
        </template>
        <RelatedInstitutions :items="relatedInstitutions ?? []" />
      </Deferred>
    </template>

    <template #files>
      <div class="space-y-6">
        <Suspense v-if="institution.types.length > 0">
          <SimpleFileViewer :fileable="{ id: institution.id, type: 'Institution' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center text-sm text-muted-foreground">
              {{ $t('Kraunami susiję failai...') }}
            </div>
          </template>
        </Suspense>
        <FileManager :starting-path="institution.sharepointPath ?? undefined" :fileable="{ id: institution.id, type: 'Institution' }" />
      </div>
    </template>

    <template #tasks>
      <Deferred data="tasks">
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
      </Deferred>
    </template>

    <template #activity>
      <RecordActivity
        subject-type="institution"
        :subject-id="institution.id"
        commentable-type="institution"
        :commentable-id="institution.id"
      />
    </template>
  </RecordPage>

  <AssignDutyUserSheet
    v-model:open="assignSheetOpen"
    :duty="sheetDuty"
    :dutiable="sheetDutiable"
    :user="sheetUser"
    :study-programs="management?.studyPrograms ?? []"
    :taken-ids
    :occupied-places
  />

  <AddCheckInDialog
    v-if="showCheckInModal"
    :open="showCheckInModal"
    :institution-id="institution.id"
    :initial-start-date="checkInRange.start"
    :initial-end-date="checkInRange.end"
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
    :description="$t('Posėdis bus perkeltas į šiukšlinę.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="deleteMeeting"
  />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue';
import { Deferred, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Bell,
  BellOff,
  Calendar as CalendarIcon,
  CalendarRange,
  Clock,
  Edit3,
  ExternalLink,
  Eye,
  EyeOff,
  Globe,
} from 'lucide-vue-next';

import { CadenceSection } from '@/Components/Cadences';
import { InstitutionIconFilled } from '@/Components/icons';
import InstitutionDutiesSection from '@/Components/Institutions/InstitutionDutiesSection.vue';
import InstitutionMeetingsList from '@/Components/Institutions/InstitutionMeetingsList.vue';
import InstitutionOverviewSection from '@/Components/Institutions/InstitutionOverviewSection.vue';
import InstitutionScopeBadge from '@/Components/Institutions/InstitutionScopeBadge.vue';
import { SecretariesSection, type SecretaryRoster, type SecretaryUser } from '@/Components/Institutions';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { ActionDescriptor } from '@/Components/Layouts/RecordPageAction.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { useActionWindow } from '@/Composables/useActionWindow';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { useShowPageData } from '@/Composables/useShowPageData';
import { getSuggestedCheckInRange, type TaskDisplayData } from '@/Composables/useTaskPresentation';
import { countIncompleteTasks } from '@/Composables/useTaskUrgency';
import { institutionActivityStatuses, type StatusPresentation } from '@/Constants/statuses';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { AssignDutyUserSheet } from '@/Features/Admin/Occupancy';
import SimpleFileViewer from '@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { useInstitutionSubscription } from '@/Pages/Admin/Dashboard/Composables/useInstitutionSubscription';
import { InstitutionActivityStatus, InstitutionScope, ModelEnum } from '@/Types/enums';
import type {
  InstitutionOverviewData,
  InstitutionPageData,
  InstitutionPageDuty,
  InstitutionPageMeeting,
  InstitutionPageRelatedInstitution,
  InstitutionPageTask,
} from '@/Types/InstitutionPage';
import type { CadenceRow } from '@/Components/Cadences';
import type { DutyWithUsers, UserWithPivot } from '@/Components/AdminForms/DutyCard.vue';

const props = defineProps<{
  institution: InstitutionPageData & { tenant?: { id: number; shortname: string } | null };
  overview: InstitutionOverviewData;
  can: { update: boolean; delete: boolean };
  duties?: InstitutionPageDuty[];
  meetings?: InstitutionPageMeeting[];
  tasks?: InstitutionPageTask[];
  relatedInstitutions?: InstitutionPageRelatedInstitution[];
  /** Deferred, and null unless the user may update the institution. */
  management?: {
    cadences: CadenceRow[];
    globalCadences: CadenceRow[];
    cadenceDefaults: { default_start_month_day: string; default_end_month_day: string };
    secretaryRosters: SecretaryRoster[];
    suggestedSecretaries: SecretaryUser[];
    studyPrograms: (App.Entities.StudyProgram & { tenant_id?: number | null })[];
  } | null;
  subscription?: {
    is_followed: boolean;
    is_muted: boolean;
    is_duty_based: boolean;
  } | null;
}>();

const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));
const RelatedInstitutions = defineAsyncComponent(() => import('@/Components/Carousels/RelatedInstitutions.vue'));
const FileManager = defineAsyncComponent(() => import('@/Features/Admin/SharepointFileManager/SharepointFileManager.vue'));

// --- Sections ---------------------------------------------------------------------------------

const { currentTab: currentSection } = useShowPageData({
  tabKey: 'institution',
  entityId: props.institution.id,
  defaultTab: 'overview',
});

/** Dropped rather than disabled when there is nothing to show — a tab that cannot be opened is worse than none. */
const tabs = computed<RecordPageSection[]>(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'duties', label: $t('Pareigybės'), count: props.institution.duties_count },
  { value: 'meetings', label: $t('Posėdžiai'), count: props.institution.meetings_count },
  ...(props.can.update ? [{ value: 'terms', label: $t('Kadencijos ir sekretoriai') }] : []),
  ...(props.institution.related_institutions_count > 0
    ? [{ value: 'related', label: $t('Ryšiai'), count: props.institution.related_institutions_count }]
    : []),
  { value: 'files', label: $t('Failai') },
  { value: 'tasks', label: $t('Užduotys'), count: countIncompleteTasks(props.tasks ?? []) },
]);

// --- Title band -------------------------------------------------------------------------------

const primaryType = computed(() => {
  const type = props.institution.types?.[0];

  return typeof type?.title === 'string' ? type.title : null;
});

/** The normal, healthy state paints no badge. */
const activityBadge = computed<StatusPresentation | undefined>(() => {
  const status = props.overview.activity_status.status as InstitutionActivityStatus;

  return status === InstitutionActivityStatus.Healthy ? undefined : institutionActivityStatuses[status];
});

const filledPositions = computed(() => props.overview.current_users.length);
const totalPositions = computed(() => props.overview.duties.reduce((sum, duty) => sum + Number(duty.places_to_occupy ?? 0), 0));

const recordFacts = computed<RecordFact[]>(() => {
  const facts: RecordFact[] = [];

  if (props.institution.tenant?.shortname) {
    facts.push({ key: 'tenant', label: $t('Padalinys'), value: props.institution.tenant.shortname });
  }

  facts.push({
    key: 'members',
    label: $t('Nariai'),
    value: totalPositions.value > 0 ? `${filledPositions.value} / ${totalPositions.value}` : String(filledPositions.value),
  });

  if (props.institution.meeting_periodicity_days) {
    facts.push({
      key: 'periodicity',
      label: $t('Susitikimų periodiškumas'),
      value: `${props.institution.meeting_periodicity_days} ${$t('d.')}`,
    });
  }

  if (props.institution.managers?.length) {
    facts.push({ key: 'managers', label: $t('Koordinatoriai'), value: props.institution.managers.map(manager => manager.name).join(', ') });
  }

  return facts;
});

// --- Permissions ------------------------------------------------------------------------------

const permissions = computed(() => usePage().props.auth?.can as Record<string, boolean> | undefined);
const canScheduleMeeting = computed(() => permissions.value?.['meetings.create.padalinys'] ?? false);
const canAddCheckIn = computed(() => permissions.value?.['institution_check_ins.create.padalinys'] ?? false);
const canDeleteMeetings = computed(() => permissions.value?.['meetings.delete.padalinys'] ?? false);

// --- Subscription (Sekti / Nutildyti) ---------------------------------------------------------

const isFollowed = ref(props.subscription?.is_followed ?? false);
const isMuted = ref(props.subscription?.is_muted ?? false);
const isDutyBased = computed(() => props.subscription?.is_duty_based ?? false);

const subscriptionState = computed(() => ({
  is_followed: isFollowed.value,
  is_muted: isMuted.value,
  is_duty_based: isDutyBased.value,
}));

const { toggleFollow: doToggleFollow, toggleMute: doToggleMute } = useInstitutionSubscription();

const toggleFollow = async () => {
  if (isDutyBased.value) {
    return;
  }

  const next = await doToggleFollow(String(props.institution.id), subscriptionState.value, ['subscription']);
  isFollowed.value = next;

  if (!next) {
    isMuted.value = false;
  }
};

const toggleMute = async () => {
  if (isDutyBased.value) {
    return;
  }

  isMuted.value = await doToggleMute(String(props.institution.id), subscriptionState.value, ['subscription']);
};

// One primary action; everything else lives in ⋯ (.ai/rules/js-pages-admin.md).
const primaryAction = computed<ActionDescriptor | undefined>(() =>
  canScheduleMeeting.value ? { key: 'meeting', label: $t('Fiksuoti posėdį'), icon: CalendarIcon } : undefined);

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

  if (canAddCheckIn.value) {
    actions.push({ key: 'check-in', label: $t('Pridėti pažymą'), icon: Clock });
  }

  actions.push({ key: 'timeline', label: $t('dutiables.timeline.open'), icon: CalendarRange });
  actions.push({ key: 'public', label: $t('Atidaryti vusa.lt'), icon: ExternalLink, href: publicUrl.value, external: true });

  if (props.subscription) {
    actions.push({
      key: 'follow',
      label: isFollowed.value ? $t('Nebesekti') : $t('Sekti'),
      icon: isFollowed.value ? EyeOff : Eye,
    });

    if (isFollowed.value) {
      actions.push({ key: 'mute', label: isMuted.value ? $t('Įjungti pranešimus') : $t('Nutildyti'), icon: isMuted.value ? Bell : BellOff });
    }
  }

  return actions;
});

const handleRecordAction = (key: string) => {
  switch (key) {
    case 'meeting':
      openMeetingWindow();
      break;
    case 'edit':
      router.visit(route('institutions.edit', props.institution.id));
      break;
    case 'check-in':
      openCheckInModal();
      break;
    case 'timeline':
      router.visit(route('dutiables.timeline', { institution: props.institution.id }));
      break;
    case 'follow':
      void toggleFollow();
      break;
    case 'mute':
      void toggleMute();
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

const showCheckInModal = ref(false);
const checkInRange = computed(() => getSuggestedCheckInRange(null));

const openCheckInModal = () => {
  showCheckInModal.value = true;
};

const taskManagerTasks = computed(
  () => (props.tasks ?? []) as unknown as InstanceType<typeof TaskManager>['$props']['tasks'],
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
  actionWindow.open({
    flow: 'institution.report',
    institution: {
      id: props.institution.id,
      name: props.institution.name,
      isInternal: props.institution.governance_scope === InstitutionScope.Vusa,
    },
  });
};

// --- Meetings tab -----------------------------------------------------------------------------

const deleteMeetingOpen = ref(false);
const meetingToDelete = ref<InstitutionPageMeeting | null>(null);

const askDeleteMeeting = (meeting: InstitutionPageMeeting) => {
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
const sheetDuty = ref<(App.Entities.Duty & Record<string, unknown>) | null>(null);
const sheetDutiable = ref<(App.Entities.Dutiable & Record<string, unknown>) | null>(null);
const sheetUser = ref<App.Entities.User | null>(null);

/** Who already holds the duty right now cannot be assigned to it again. */
const holdsNow = (duty: DutyWithUsers | null) => (duty?.current_users ?? []);
const takenIds = computed(() => holdsNow(sheetDuty.value as DutyWithUsers | null).map(user => String(user.id)));
const occupiedPlaces = computed(() => holdsNow(sheetDuty.value as DutyWithUsers | null).length);

const openAssignSheet = (duty: DutyWithUsers) => {
  sheetDuty.value = duty as unknown as App.Entities.Duty & Record<string, unknown>;
  sheetDutiable.value = null;
  sheetUser.value = null;
  assignSheetOpen.value = true;
};

const openTermSheet = (duty: DutyWithUsers, user: UserWithPivot) => {
  sheetDuty.value = duty as unknown as App.Entities.Duty & Record<string, unknown>;
  sheetDutiable.value = (user.pivot ?? null) as (App.Entities.Dutiable & Record<string, unknown>) | null;
  sheetUser.value = user;
  assignSheetOpen.value = true;
};

// --- Spotlight (secretaries moved here from the form) -----------------------------------------

const secretariesSpotlight = useFeatureSpotlight('institution-secretaries-v1');

usePageBreadcrumbs(
  BreadcrumbHelpers.adminShow(
    'Institucijos',
    'institutions.index',
    {},
    props.institution.name,
    InstitutionIconFilled,
    InstitutionIconFilled,
  ),
);

</script>
