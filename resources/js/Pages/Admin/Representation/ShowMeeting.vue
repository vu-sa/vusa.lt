<template>
  <RecordPage
    v-model:section="currentTab"
    :title="meetingTitle"
    :entity-type="ModelEnum.MEETING"
    :status="meetingStatus"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    :navigation="recordNavigation"
    @action="handleRecordAction"
  >
    <template #identity>
      <MeetingDatePlate :value="meeting.start_time" />
    </template>

    <template #subtitle>
      <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
        <span>{{ heroSubtitle }}</span>
        <span aria-hidden="true">·</span>
        <span>{{ meetingRelativeTime }}</span>
      </div>
    </template>

    <template #fact-people>
      <div class="flex flex-col gap-2">
        <UsersAvatarGroup v-if="representatives.length" :users="representatives" :max="4" :size="24" expandable />
        <div v-if="resolvedSecretaries.length" class="flex items-center gap-2">
          <span class="text-xs text-muted-foreground">{{ $t('secretaries.label') }}</span>
          <UsersAvatarGroup :users="(resolvedSecretaries as unknown as App.Entities.User[])" :max="3" :size="22" expandable />
        </div>
        <span v-if="!representatives.length && !resolvedSecretaries.length">—</span>
      </div>
    </template>

    <template #fact-visibility>
      <a v-if="publicUrl" :href="publicUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 underline underline-offset-4">
        <Globe class="size-4" />
        {{ $t('Matoma vusa.lt') }}
      </a>
      <span v-else>{{ $t('Tik viduje') }}</span>
    </template>

    <template #alert>
      <MeetingCompletionChecklist
        :actions="completion.missingActions"
        @select="handleMissingAction"
      />
    </template>

    <template #agenda>
      <div class="space-y-6">
        <MeetingAgendaList
          v-model:editing="agendaEditing"
          :agenda-items="meeting.agenda_items ?? []"
          :meeting-id="meeting.id"
          :can-add="abilities.createAgendaItems"
          :can-reorder="abilities.reorderAgendaItems"
          :requires-student-perspective="!isInternalBody"
          @add="showSingleAgendaItemModal = true"
          @add-bulk="openBulkAgendaModal"
          @delete="requestAgendaItemDelete"
        />
      </div>
    </template>

    <template #documents>
      <Deferred data="documents">
        <template #fallback>
          <div class="space-y-3">
            <Skeleton class="h-10 w-full" />
            <Skeleton class="h-24 w-full" />
          </div>
        </template>
        <MeetingDocumentsPanel
          :meeting-id="meeting.id"
          :documents="documents ?? []"
          :institution-ids
          :tenant-shortnames
          can-update
        />
      </Deferred>
    </template>

    <template #files>
      <FileManager :starting-path="meeting.sharepointPath" :fileable="{ id: meeting.id, type: 'Meeting' }" />
    </template>

    <template #tasks>
      <Deferred data="tasks">
        <template #fallback>
          <div class="space-y-3">
            <Skeleton class="h-10 w-full" />
            <Skeleton class="h-24 w-full" />
          </div>
        </template>
        <TaskManager
          :taskable="{ id: meeting.id, type: ModelEnum.MEETING }"
          :tasks="tasks ?? []"
          @open-meeting-modal="openMeetingModal"
          @open-check-in-dialog="openCheckInDialog"
          @open-task-detail="openTaskDetail"
        />
      </Deferred>
    </template>

    <template #activity>
      <RecordActivity subject-type="meeting" :subject-id="meeting.id" commentable-type="meeting" :commentable-id="meeting.id" />
    </template>

    <Deferred data="coordinator">
      <template #fallback>
        <Skeleton class="mt-10 h-16 w-full" />
      </template>
      <CoordinatorCard :coordinator="coordinator ?? null" class="mt-10" compact />
    </Deferred>

    <!-- Modals -->
    <Dialog v-model:open="showMeetingModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t("Redaguoti posėdžio datą") }}</DialogTitle>
        </DialogHeader>
        <Suspense>
          <MeetingForm class="mt-2" :meeting @submit="handleMeetingFormSubmit" />
        </Suspense>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showSingleAgendaItemModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Plus class="h-5 w-5" />
            {{ $t("Pridėti darbotvarkės punktą") }}
          </DialogTitle>
        </DialogHeader>
        <AddAgendaItemForm :meeting-id="meeting.id" :loading @submit="handleSingleAgendaItemSubmit" />
        <div class="mt-4 pt-4 border-t">
          <Button variant="outline" size="sm" class="w-full" @click="showSingleAgendaItemModal = false; openBulkAgendaModal();">
            {{ $t("Pridėti kelis punktus iš karto") }}...
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showAgendaItemStoreModal">
      <DialogContent class="max-h-[85vh] sm:max-w-3xl flex flex-col">
        <DialogHeader class="flex-none">
          <DialogTitle>{{ $t("Pridėti darbotvarkės punktus") }}</DialogTitle>
        </DialogHeader>
        <div class="flex-1 overflow-y-auto -mx-6 px-6">
          <AgendaItemsForm
            :key="bulkAgendaInitialInput"
            class="w-full"
            :loading
            mode="add"
            :initial-input="bulkAgendaInitialInput"
            :submit-label="$t('Pridėti punktus')"
            :show-skip-button="false"
            @submit="handleAgendaItemsFormSubmit"
          />
        </div>
      </DialogContent>
    </Dialog>

    <!-- Add Institution Dialog -->
    <Dialog v-model:open="showAddInstitutionDialog">
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Link2 class="h-4 w-4" />
            {{ $t('Pridėti instituciją') }}
          </DialogTitle>
          <DialogDescription class="text-sm text-muted-foreground mt-2">
            Retais atvejais, atstovavimo organai gali turėti bendrų posėdžių. Jei šis posėdis yra bendras su kitomis institucijomis, pasirinkite jas.
            Galite pasirinkti tik iš susijusių institucijų.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 pt-2">
          <Select v-model="addInstitutionId">
            <SelectTrigger>
              <SelectValue :placeholder="$t('Pasirinkite instituciją')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="option in availableInstitutionsToAdd"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="availableInstitutionsToAdd.length === 0" class="text-sm text-muted-foreground">
            {{ $t('Nėra galimų institucijų pridėti.') }}
          </p>
          <div class="flex justify-end gap-2">
            <Button variant="outline" size="sm" @click="showAddInstitutionDialog = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button size="sm" :disabled="!addInstitutionId" @click="handleAttachInstitution">
              {{ $t('Pridėti') }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Agenda Item Delete Confirmation -->
    <Dialog v-model:open="showAgendaItemDeleteDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-destructive">
            <AlertTriangle class="h-5 w-5" />
            {{ $t("Šalinti darbotvarkės punktą?") }}
          </DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <p class="text-sm text-muted-foreground">
            {{ $t("Ar tikrai norite ištrinti šį darbotvarkės punktą? Šis veiksmas negrįžtamas.") }}
          </p>
          <p v-if="agendaItemPendingDelete" class="border border-border bg-secondary/60 px-3 py-2 text-sm font-medium text-foreground">
            {{ agendaItemPendingDelete.title }}
          </p>
          <div class="flex justify-end gap-3">
            <Button variant="outline" @click="showAgendaItemDeleteDialog = false">
              {{ $t("Atšaukti") }}
            </Button>
            <Button variant="destructive" @click="confirmAgendaItemDelete">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t("Šalinti") }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-destructive">
            <AlertTriangle class="h-5 w-5" />
            {{ $t("Šalinti posėdį?") }}
          </DialogTitle>
        </DialogHeader>

        <div class="space-y-4">
          <p class="text-sm text-muted-foreground">
            {{ $t("Ar tikrai norite ištrinti šį posėdį? Šis veiksmas negrįžtamas ir bus pašalinti visi su posėdžiu susiję duomenys, įskaitant darbotvarkės punktus.") }}
          </p>

          <div class="border border-[var(--status-danger-border)] bg-[var(--status-danger-surface)] p-3 text-[var(--status-danger)]">
            <p class="text-sm font-medium">
              {{ $t("Bus ištrinta:") }}
            </p>
            <ul class="mt-1 space-y-1 text-xs">
              <li>• {{ meeting.agenda_items?.length ?? 0 }} {{ $t("darbotvarkės punktai") }}</li>
              <li v-if="tasks?.length">
                • {{ tasks.length }} {{ $t("užduotys") }}
              </li>
              <li v-if="meeting.files && meeting.files.length">
                • {{ meeting.files.length }} {{ $t("failai") }}
              </li>
            </ul>
          </div>

          <div class="flex justify-end gap-3">
            <Button variant="outline" @click="showDeleteDialog = false">
              {{ $t("Atšaukti") }}
            </Button>
            <Button variant="destructive" @click="handleMeetingDelete">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t("Šalinti posėdį") }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
    <AnnounceMeetingDialog
      v-if="isInternalBody"
      v-model:open="showAnnounceDialog"
      :meeting-id="meeting.id"
      :tenant-ids
    />

    <!-- Task detail dialog: the meeting's own tasks are always meeting-taskable (agenda
         creation/completion), so the check-in flow below is unreachable in practice — wired
         anyway so "View details" never silently does nothing. -->
    <TaskDetailDialog
      v-if="selectedDetailTask"
      :open="showTaskDetail"
      :task="selectedDetailTask"
      @close="closeTaskDetail"
      @schedule-meeting="scheduleMeetingFromDetail"
      @report-no-meeting="reportNoMeetingFromDetail"
    />
    <AddCheckInDialog
      v-if="selectedCheckInTask"
      :open="showCheckInDialog"
      :institution-id="selectedCheckInTask.taskable_id"
      :institution-name="selectedCheckInTask.taskable?.name"
      :initial-start-date="checkInStartDate"
      :initial-end-date="checkInEndDate"
      @close="closeCheckInDialog"
    />
  </RecordPage>
</template>

<script setup lang="tsx">
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue';
import { Deferred, router, useForm } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, CalendarPlus, CalendarX, Copy, Edit, Globe, Link2, Plus, Trash2 } from 'lucide-vue-next';
import { DialogDescription } from 'reka-ui';

import { InstitutionScope, ModelEnum } from '@/Types/enums';
import { formatRelativeTime } from '@/Utils/IntlTime';
import { formatMeetingDateTime, formatMeetingTimeOnly } from '@/Utils/MeetingDisplay';
import { genitivizeEveryWord } from '@/Utils/String';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useMeetingUrgency } from '@/Composables/useMeetingUrgency';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statuses';
import RecordPage, { type RecordAction, type RecordFact, type RecordNavigationContext } from '@/Components/Layouts/RecordPage.vue';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import CoordinatorCard from '@/Components/Home/CoordinatorCard.vue';
import type { HomeCoordinator } from '@/Components/Home/types';
import MeetingAgendaList from '@/Components/Meetings/MeetingAgendaList.vue';
import MeetingCompletionChecklist, { type MeetingMissingAction } from '@/Components/Meetings/MeetingCompletionChecklist.vue';
import MeetingDatePlate from '@/Components/Meetings/MeetingDatePlate.vue';
import AddAgendaItemForm from '@/Components/AdminForms/AddAgendaItemForm.vue';
import AgendaItemsForm from '@/Components/AdminForms/Special/AgendaItemsForm.vue';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import AnnounceMeetingDialog from '@/Components/Meetings/AnnounceMeetingDialog.vue';
import MeetingDocumentsPanel from '@/Components/Meetings/MeetingDocumentsPanel.vue';
import type { SecretaryUser } from '@/Components/Institutions';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { InstitutionIconFilled, MeetingIconFilled } from '@/Components/icons';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import { countIncompleteTasks } from '@/Composables/useTaskUrgency';

interface MeetingAbilities {
  update: boolean;
  delete: boolean;
  createAgendaItems: boolean;
  reorderAgendaItems: boolean;
  attachInstitution: boolean;
}

interface MeetingCompletion {
  status: MeetingCompletionStatus;
  missingActions: MeetingMissingAction[];
}

const props = withDefaults(defineProps<{
  meeting: App.Entities.Meeting;
  representatives: App.Entities.User[];
  /** Institution secretaries resolved at the meeting's own date (O22). */
  secretaries?: SecretaryUser[];
  administrators?: SecretaryUser[];
  availableInstitutionsForAttach?: { id: string; name: string; tenant_shortname?: string | null }[] | null;
  governanceScope?: string;
  abilities?: MeetingAbilities;
  completion?: MeetingCompletion;
  publicUrl?: string | null;
  recordNavigation?: RecordNavigationContext;
  tasks?: InstanceType<typeof TaskManager>['$props']['tasks'];
  documents?: NonNullable<App.Entities.Meeting['documents']>;
  coordinator?: HomeCoordinator | null;
}>(), {
  secretaries: () => [],
  administrators: () => [],
  availableInstitutionsForAttach: () => [],
  governanceScope: undefined,
  abilities: () => ({
    update: false,
    delete: false,
    createAgendaItems: false,
    reorderAgendaItems: false,
    attachInstitution: false,
  }),
  completion: () => ({ status: 'no_items', missingActions: [] }),
  publicUrl: null,
  recordNavigation: undefined,
  tasks: undefined,
  documents: undefined,
  coordinator: null,
});

const resolvedSecretaries = computed(() => props.secretaries ?? props.administrators ?? []);

const institutionIds = computed(() => props.meeting.institutions?.map(institution => institution.id) ?? []);
const tenantIds = computed(() =>
  (props.meeting.institutions ?? [])
    .map(institution => institution.tenant_id)
    .filter((id): id is number => typeof id === 'number'),
);
/** Names the documents picker also accepts, next to the meeting's own institutions. */
const tenantShortnames = computed(() =>
  [...new Set(
    (props.meeting.institutions ?? [])
      .map(institution => institution.tenant?.shortname)
      .filter((name): name is string => typeof name === 'string'),
  )],
);

/**
 * Nutarimai and protokolai are how VU SA's own bodies keep their record. VU/national bodies file
 * their paperwork through SharePoint on the meeting itself, so the tab would only be noise there.
 */
const isInternalBody = computed(() => props.governanceScope === InstitutionScope.Vusa);

/**
 * The calendar event standing for this meeting. Publishing it is what opens the agenda to the
 * public — see Meeting::isPubliclyVisible() on the backend.
 */
const calendarEvent = computed(() => props.meeting.calendar_event ?? null);

const AddCheckInDialog = defineAsyncComponent(() => import('@/Components/Institutions/AddCheckInDialog.vue'));
const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

const {
  showCheckInDialog,
  showTaskDetail,
  selectedCheckInTask,
  selectedDetailTask,
  checkInStartDate,
  checkInEndDate,
  openMeetingModal,
  openCheckInDialog,
  closeCheckInDialog,
  openTaskDetail,
  closeTaskDetail,
  scheduleMeetingFromDetail,
  reportNoMeetingFromDetail,
} = useTaskActionDialogs();

const showAnnounceDialog = ref(false);

const openAnnounceDialog = () => {
  showAnnounceDialog.value = true;
};

const handleUnlinkCalendarEvent = () => {
  router.delete(route('meetings.calendarEvent.destroy', { meeting: props.meeting.id }), {
    preserveScroll: true,
  });
};

// File availability is shown as facts, not completion requirements.
const { hasProtocol, hasReport, isPastMeeting } = useMeetingUrgency(() => props.meeting);

// Hide HH:MM for email/electronic meetings (start_time is forced to 23:59 as a deadline marker)
const meetingTimeLabel = computed(() => formatMeetingTimeOnly(props.meeting));
const meetingRelativeTime = computed(() => formatRelativeTime(new Date(props.meeting.start_time)));
const meetingStatus = computed(() => meetingCompletionStatuses[props.completion.status]);

// Component state
const showMeetingModal = ref(false);
const showAgendaItemStoreModal = ref(false);

// The action window's "paste the whole agenda" choice promises the paste box, not the
// one-by-one editor the menu's own bulk entry opens on.
const bulkAgendaInitialInput = ref<'one-by-one' | 'text'>('one-by-one');
const showSingleAgendaItemModal = ref(false);
const showDeleteDialog = ref(false);
const loading = ref(false);

// Read-only by default; toggled on to add/reorder/delete agenda items
const agendaEditing = ref(false);

// Tab state with smart defaults (agenda is the landing tab)
// Kept in step with `tabs` below; guards the `?tab=` URL param.
const TAB_NAMES = computed(() => (isInternalBody.value
  ? ['agenda', 'documents', 'files', 'tasks']
  : ['agenda', 'files', 'tasks']));
const storedTab = useStorage('show-meeting-tab', 'agenda');

// Check URL for tab parameter (priority over localStorage)
const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');
    if (urlTab && TAB_NAMES.value.includes(urlTab)) {
      return urlTab;
    }
  }
  return TAB_NAMES.value.includes(storedTab.value) ? storedTab.value : 'agenda';
};

const currentTab = ref(getInitialTab());

// Sync tab state to URL without page reload
watch(currentTab, (newTab) => {
  storedTab.value = newTab;

  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    if (newTab === 'agenda') {
      url.searchParams.delete('tab');
    }
    else {
      url.searchParams.set('tab', newTab);
    }
    url.searchParams.delete('action');
    window.history.replaceState({}, '', url.toString());
  }
});

const tabs = computed(() => [
  { value: 'agenda', label: $t('Darbotvarkė'), count: props.meeting.agenda_items?.length },
  ...(isInternalBody.value
    ? [{ value: 'documents', label: $t('Dokumentai'), count: props.documents?.length }]
    : []),
  { value: 'files', label: $t('Failai') },
  // Outstanding only: a finished task is not something the reader still has to act on.
  { value: 'tasks', label: $t('Užduotys'), count: countIncompleteTasks(props.tasks ?? []) },
]);

const recordFacts = computed<RecordFact[]>(() => [
  {
    key: 'institution',
    label: $t('Institucija'),
    value: props.meeting.institutions?.map(institution => institution.name).join(' · ') || $t('Be institucijos'),
  },
  {
    key: 'time',
    label: $t('Laikas ir tipas'),
    value: [meetingTimeLabel.value, props.meeting.type_label].filter(Boolean).join(' · ') || '—',
  },
  { key: 'people', label: $t('Atstovai ir sekretoriai') },
  {
    key: 'visibility',
    label: $t('Matomumas'),
    value: props.publicUrl ? $t('Matoma vusa.lt') : $t('Tik viduje'),
  },
  {
    key: 'protocol',
    label: $t('Protokolas'),
    value: isPastMeeting.value
      ? (hasProtocol.value ? $t('Įkeltas') : $t('Neįkeltas'))
      : $t('Dar neaktualu'),
  },
  {
    key: 'report',
    label: $t('Ataskaita'),
    value: isPastMeeting.value
      ? (hasReport.value ? $t('Įkelta') : $t('Neįkelta'))
      : $t('Dar neaktualu'),
  },
]);

const primaryAction = computed<RecordAction | undefined>(() => {
  if (props.completion.missingActions.length && props.abilities.createAgendaItems) {
    return { key: 'complete', label: $t('Papildyti'), icon: Plus };
  }

  if (props.abilities.update) {
    return { key: 'edit', label: $t('Redaguoti posėdį'), icon: Edit };
  }

  return undefined;
});

const overflowActions = computed<RecordAction[]>(() => {
  const actions: RecordAction[] = [];

  if (props.abilities.update && primaryAction.value?.key !== 'edit') {
    actions.push({ key: 'edit', label: $t('Redaguoti posėdį'), icon: Edit });
  }

  if (props.abilities.attachInstitution && availableInstitutionsToAdd.value.length) {
    actions.push({ key: 'attach-institution', label: $t('Pridėti instituciją'), icon: Link2 });
  }

  if (props.abilities.update && (props.meeting.institutions?.length ?? 0) > 1) {
    props.meeting.institutions?.forEach((institution) => {
      actions.push({
        key: `detach-institution:${institution.id}`,
        label: `${$t('Pašalinti instituciją')}: ${institution.name}`,
        icon: Link2,
        destructive: true,
      });
    });
  }

  if (props.abilities.update && isInternalBody.value && !calendarEvent.value) {
    actions.push({ key: 'announce', label: $t('Paskelbti kalendoriuje'), icon: CalendarPlus });
  }
  else if (props.abilities.update && calendarEvent.value) {
    actions.push({ key: 'unlink-calendar', label: $t('Atsieti nuo kalendoriaus'), icon: CalendarX });
  }

  actions.push({ key: 'copy-link', label: $t('Kopijuoti nuorodą'), icon: Copy });

  if (props.abilities.delete) {
    actions.push({ key: 'delete', label: $t('Šalinti posėdį'), icon: Trash2, destructive: true });
  }

  return actions;
});

onMounted(() => {
  const lastVisitedMeetingId = useStorage('last-visited-meeting-id', '');
  const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
  const urlTab = params?.get('tab');
  const urlAction = params?.get('action');

  // Reset to the agenda tab for a freshly visited meeting (unless URL pins a tab)
  if (!urlTab && lastVisitedMeetingId.value !== props.meeting.id) {
    currentTab.value = 'agenda';
  }
  lastVisitedMeetingId.value = props.meeting.id;

  // Auto-open an agenda dialog when the caller asked for one (`?action=add`, or
  // `?action=add-bulk` from the action window's "paste the whole agenda" choice).
  if (urlAction === 'add' || urlAction === 'add-bulk') {
    currentTab.value = 'agenda';
    setTimeout(() => {
      agendaEditing.value = true;
      if (urlAction === 'add-bulk') {
        bulkAgendaInitialInput.value = 'text';
        showAgendaItemStoreModal.value = true;
      }
      else {
        showSingleAgendaItemModal.value = true;
      }
      if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        window.history.replaceState({}, '', url.toString());
      }
    }, 100);
  }
});

// Form handling
const meetingAgendaForm = useForm({
  meeting: props.meeting.id,
  agendaItems: [],
});

// Computed values
const mainInstitution: App.Entities.Institution | string
  = props.meeting.institutions?.[0] ?? 'Be institucijos';

const isJoint = computed(
  () => (props.meeting as App.Entities.Meeting & { is_joint?: boolean }).is_joint
    ?? (props.meeting.institutions?.length ?? 0) > 1,
);

// Always derive the displayed title from start_time + institution.
// The stored `meeting.title` is auto-generated server-side and historically
// embedded a 23.59 timestamp for email meetings — recomputing here keeps the
// header/breadcrumb correct without a data backfill.
const meetingTitle = computed(() => {
  const datePart = formatMeetingDateTime(props.meeting, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  if (isJoint.value) {
    return `${datePart} jungtinis posėdis`;
  }

  const institutionName = typeof mainInstitution === 'string'
    ? mainInstitution
    : mainInstitution.name;

  return `${datePart} ${genitivizeEveryWord(institutionName)} posėdis`;
});

// Hero subtitle - institution name(s) with link
const heroSubtitle = computed(() => {
  if (isJoint.value) {
    return props.meeting.institutions?.map((i: App.Entities.Institution) => i.name).join(' · ') ?? '';
  }
  if (typeof mainInstitution === 'string') {
    return mainInstitution;
  }
  return mainInstitution.name;
});

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() => {
  if (typeof mainInstitution === 'string') {
    return [
      { label: mainInstitution, icon: InstitutionIconFilled },
      { label: meetingTitle.value, icon: MeetingIconFilled },
    ];
  }

  return BreadcrumbHelpers.adminShow(
    mainInstitution.name,
    'institutions.show',
    { institution: mainInstitution.id },
    meetingTitle.value,
    InstitutionIconFilled,
    MeetingIconFilled,
  );
});

// Joint meeting — institution management
const showAddInstitutionDialog = ref(false);
const addInstitutionId = ref('');

const availableInstitutionsToAdd = computed(() => {
  const attached = new Set(props.meeting.institutions?.map((i: App.Entities.Institution) => i.id) ?? []);
  return (props.availableInstitutionsForAttach ?? [])
    .filter(inst => !attached.has(inst.id))
    .map(inst => ({
      label: inst.tenant_shortname ? `${inst.name} (${inst.tenant_shortname})` : inst.name,
      value: inst.id,
    }));
});

const handleAttachInstitution = () => {
  if (!addInstitutionId.value) {
    return;
  }
  router.post(route('meetings.institutions.attach', props.meeting.id), {
    institution_id: addInstitutionId.value,
  }, {
    onSuccess: () => {
      showAddInstitutionDialog.value = false;
      addInstitutionId.value = '';
    },
  });
};

const handleDetachInstitution = (institutionId: string) => {
  router.delete(route('meetings.institutions.detach', { meeting: props.meeting.id, institution: institutionId }));
};

const handleMissingAction = (action: MeetingMissingAction) => {
  if (action.type === 'agenda_missing') {
    currentTab.value = 'agenda';
    agendaEditing.value = true;
    bulkAgendaInitialInput.value = 'text';
    showAgendaItemStoreModal.value = true;
    return;
  }

  router.visit(route('agendaItems.edit', {
    agendaItem: action.agenda_item_id,
    mode: 'edit',
    focus: action.type === 'agenda_item_type_missing' ? 'type' : 'votes',
  }));
};

const handleRecordAction = (action: string) => {
  if (action === 'complete') {
    document.getElementById('meeting-completion')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  if (action === 'edit') {
    showMeetingModal.value = true;
    return;
  }
  if (action === 'attach-institution') {
    showAddInstitutionDialog.value = true;
    return;
  }
  if (action === 'announce') {
    openAnnounceDialog();
    return;
  }
  if (action === 'unlink-calendar') {
    handleUnlinkCalendarEvent();
    return;
  }
  if (action === 'copy-link') {
    void navigator.clipboard?.writeText(window.location.href);
    return;
  }
  if (action === 'delete') {
    showDeleteDialog.value = true;
    return;
  }
  if (action.startsWith('detach-institution:')) {
    handleDetachInstitution(action.slice('detach-institution:'.length));
  }
};

// Event handlers
const handleMeetingFormSubmit = (meeting: App.Entities.Meeting) => {
  router.patch(route('meetings.update', props.meeting.id), meeting, {
    onSuccess: () => {
      showMeetingModal.value = false;
    },
  });
};

// Agenda item deletion goes through a confirmation dialog
const showAgendaItemDeleteDialog = ref(false);
const agendaItemPendingDelete = ref<App.Entities.AgendaItem | null>(null);

const requestAgendaItemDelete = (agendaItem: App.Entities.AgendaItem) => {
  agendaItemPendingDelete.value = agendaItem;
  showAgendaItemDeleteDialog.value = true;
};

const confirmAgendaItemDelete = () => {
  if (!agendaItemPendingDelete.value) {
    return;
  }
  router.delete(route('agendaItems.destroy', agendaItemPendingDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showAgendaItemDeleteDialog.value = false;
      agendaItemPendingDelete.value = null;
    },
  });
};

const handleSingleAgendaItemSubmit = (data: { meeting_id: string; title: string; description?: string; brought_by_students?: boolean }) => {
  loading.value = true;

  router.post(route('agendaItems.store'), {
    meeting_id: data.meeting_id,
    agendaItemTitles: [data.title],
    agendaItemDescriptions: data.description ? [data.description] : [],
    broughtByStudentsFlags: [data.brought_by_students || false],
  }, {
    onSuccess: () => {
      showSingleAgendaItemModal.value = false;
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};

const openBulkAgendaModal = () => {
  bulkAgendaInitialInput.value = 'one-by-one';
  showAgendaItemStoreModal.value = true;
};

const handleAgendaItemsFormSubmit = (agendaItems: Record<string, unknown>) => {
  loading.value = true;

  meetingAgendaForm
    .transform(data => ({
      meeting_id: props.meeting.id,
      ...agendaItems,
    }))
    .post(route('agendaItems.store'), {
      onSuccess: () => {
        meetingAgendaForm.reset();
        showAgendaItemStoreModal.value = false;
      },
      onFinish: () => {
        loading.value = false;
      },
    });
};

const handleMeetingDelete = () => {
  const redirectTo = typeof mainInstitution === 'string'
    ? route('admin.dashboard')
    : route('institutions.show', mainInstitution.id);

  router.delete(route('meetings.destroy', props.meeting.id), {
    data: { redirect_to: redirectTo },
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};
</script>
