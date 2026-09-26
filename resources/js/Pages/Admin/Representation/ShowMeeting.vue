<template>
  <RecordPage
    v-model:section="currentTab"
    :title="meetingTitle"
    :entity-type="ModelEnum.MEETING"
    :eyebrow-suffix="meeting.type_label"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    :navigation="recordNavigation"
    actions-beside-title
    @action="handleRecordAction"
  >
    <template #identity>
      <MeetingDatePlate :value="meeting.start_time" />
    </template>

    <template #fact-institution>
      <div v-if="meeting.institutions?.length" class="flex flex-wrap gap-x-1">
        <template v-for="(institution, index) in meeting.institutions" :key="institution.id">
          <span v-if="index" aria-hidden="true">·</span>
          <span v-if="readOnly">{{ institution.name }}</span>
          <Link v-else :href="route('institutions.show', institution.id)" class="text-brand underline decoration-brand/40 underline-offset-4 hover:decoration-brand">
            {{ institution.name }}
          </Link>
        </template>
      </div>
      <span v-else>{{ $t('Be institucijos') }}</span>
    </template>

    <template #fact-people>
      <UsersFactList :users="representatives" :inline-limit="1" />
    </template>

    <template #fact-visibility>
      <a v-if="publicUrl" :href="publicUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-status-success underline underline-offset-4">
        {{ $t('Matoma vusa.lt') }}
      </a>
      <span v-else>{{ $t('Tik viduje') }}</span>
    </template>

    <template #fact-after-meeting>
      <div v-if="isPastMeeting" class="space-y-0.5 text-xs font-normal">
        <component
          :is="canUploadFiles && !document.uploaded ? 'button' : 'p'"
          v-for="document in documentStatuses"
          :key="document.key"
          :type="canUploadFiles && !document.uploaded ? 'button' : undefined"
          :class="[
            'flex items-start gap-1.5 text-left',
            canUploadFiles && !document.uploaded && 'underline decoration-border underline-offset-4 hover:decoration-foreground pointer-coarse:min-h-11 pointer-coarse:items-center',
          ]"
          :data-status-role="document.uploaded ? 'success' : 'attention'"
          @click="canUploadFiles && !document.uploaded && openFilesUpload(document.fileType)"
        >
          <component
            :is="document.uploaded ? CircleCheck : CircleDashed"
            :class="['size-3.5 shrink-0', document.uploaded ? 'text-status-success' : 'text-status-attention']"
            aria-hidden="true"
          />
          <span>{{ document.label }}</span>
          <span class="sr-only">{{ document.state }}</span>
        </component>
      </div>
      <span v-else>{{ $t('meetings.record.not_yet') }}</span>
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
          @add="openAgendaSheet"
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
      <FileableFilesPanel
        ref="filesPanel"
        :fileable="{ id: meeting.id, type: 'Meeting' }"
        :files
        :can-upload="canUploadFiles"
        :can-delete="abilities.update"
        :primary-types="MEETING_PRIMARY_FILE_TYPES"
        :default-date="meeting.start_time"
      />
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
          @open-task-detail="openTaskDetail"
        />
      </Deferred>
    </template>

    <template v-if="!readOnly" #activity>
      <RecordActivity subject-type="meeting" :subject-id="meeting.id" commentable-type="meeting" :commentable-id="meeting.id" />
    </template>

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

    <AddAgendaItemsSheet
      v-model:open="showAgendaSheet"
      :meeting-id="meeting.id"
      :initial-mode="agendaSheetMode"
      :recent-agendas
    />

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

    <TaskDetailDialog
      v-if="selectedDetailTask"
      :open="showTaskDetail"
      :task="selectedDetailTask"
      @close="closeTaskDetail"
      @report="reportFromDetail"
    />
  </RecordPage>
</template>

<script setup lang="tsx">
import { ref, computed, watch, onMounted, defineAsyncComponent, nextTick } from 'vue';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, CalendarPlus, CalendarX, CircleCheck, CircleDashed, Copy, Edit, Globe, Link2, Plus, Trash2 } from 'lucide-vue-next';
import { DialogDescription } from 'reka-ui';

import { InstitutionScope, ModelEnum } from '@/Types/enums';
import { formatRelativeTime } from '@/Utils/IntlTime';
import { formatMeetingDateTime, formatMeetingTimeOnly } from '@/Utils/MeetingDisplay';
import { genitivizeEveryWord } from '@/Utils/String';
import { useMeetingUrgency } from '@/Composables/useMeetingUrgency';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statuses';
import RecordPage, { type RecordAction, type RecordFact, type RecordNavigationContext } from '@/Components/Layouts/RecordPage.vue';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import UsersFactList from '@/Components/Avatars/UsersFactList.vue';
import MeetingAgendaList from '@/Components/Meetings/MeetingAgendaList.vue';
import MeetingCompletionChecklist, { type MeetingMissingAction } from '@/Components/Meetings/MeetingCompletionChecklist.vue';
import MeetingDatePlate from '@/Components/Meetings/MeetingDatePlate.vue';
import AddAgendaItemsSheet, { type AddAgendaMode, type RecentAgenda } from '@/Components/Meetings/AddAgendaItemsSheet.vue';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import AnnounceMeetingDialog from '@/Components/Meetings/AnnounceMeetingDialog.vue';
import MeetingDocumentsPanel from '@/Components/Meetings/MeetingDocumentsPanel.vue';
import { FileableFilesPanel, type FileableFileItem } from '@/Components/Files';
import { MEETING_PRIMARY_FILE_TYPES, type FileableFileType } from '@/Constants/fileTypes';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { enterMeeting } from '@/Composables/useRecordTrail';
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
  availableInstitutionsForAttach?: { id: string; name: string; tenant_shortname?: string | null }[] | null;
  governanceScope?: string;
  abilities?: MeetingAbilities;
  completion?: MeetingCompletion;
  publicUrl?: string | null;
  recordNavigation?: RecordNavigationContext;
  tasks?: InstanceType<typeof TaskManager>['$props']['tasks'];
  documents?: NonNullable<App.Entities.Meeting['documents']>;
  recentAgendas?: RecentAgenda[] | null;
  files?: FileableFileItem[];
  /** A public meeting outside the user's reach: its agenda only (MeetingPolicy::viewSummary). */
  readOnly?: boolean;
}>(), {
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
  recentAgendas: undefined,
  files: () => [],
});

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

const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

const {
  showTaskDetail,
  selectedDetailTask,
  openTaskDetail,
  closeTaskDetail,
  reportFromDetail,
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
const documentStatuses = computed(() => [
  {
    key: 'protocol',
    fileType: 'Protokolai' as FileableFileType,
    label: $t('meetings.record.protocol'),
    uploaded: hasProtocol.value,
    state: hasProtocol.value ? $t('Įkeltas') : $t('Neįkeltas'),
  },
  {
    key: 'report',
    fileType: 'Ataskaitos' as FileableFileType,
    label: $t('meetings.record.report'),
    uploaded: hasReport.value,
    state: hasReport.value ? $t('Įkelta') : $t('Neįkelta'),
  },
]);

// The folder needs an institution with a padalinys; without one there is nowhere to upload.
const canUploadFiles = computed(() => props.abilities.update && !!props.meeting.sharepointPath);
const filesPanel = ref<InstanceType<typeof FileableFilesPanel> | null>(null);

const openFilesUpload = async (type: FileableFileType) => {
  currentTab.value = 'files';
  await nextTick();
  filesPanel.value?.openUpload(type);
};

// Component state
const showMeetingModal = ref(false);
const showAgendaSheet = ref(false);
const agendaSheetMode = ref<AddAgendaMode>('lines');
const showDeleteDialog = ref(false);

const openAgendaSheet = (mode: AddAgendaMode) => {
  agendaSheetMode.value = mode;
  showAgendaSheet.value = true;
};

// Read-only by default; toggled on to add/reorder/delete agenda items
const agendaEditing = ref(false);

const tabs = computed(() => [
  { value: 'agenda', label: $t('Darbotvarkė'), count: props.meeting.agenda_items?.length },
  ...(props.readOnly ? [] : panelTabs.value),
]);

const panelTabs = computed(() => [
  ...(isInternalBody.value
    ? [{ value: 'documents', label: $t('Dokumentai'), count: props.documents?.length }]
    : []),
  { value: 'files', label: $t('Failai') },
  // Outstanding only: a finished task is not something the reader still has to act on.
  { value: 'tasks', label: $t('Užduotys'), count: countIncompleteTasks(props.tasks ?? []) },
]);

// Tab state with smart defaults (agenda is the landing tab)
// Guards the `?tab=` URL param.
const TAB_NAMES = computed(() => tabs.value.map(tab => tab.value));
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

const recordFacts = computed<RecordFact[]>(() => [
  {
    key: 'status',
    label: $t('Būsena'),
    status: meetingStatus.value,
  },
  {
    key: 'visibility',
    label: $t('Matomumas'),
    labelIcon: Globe,
    surfaceClass: props.publicUrl ? 'bg-status-success-surface' : 'bg-status-neutral-surface',
    value: props.publicUrl ? $t('Matoma vusa.lt') : $t('Tik viduje'),
  },
  {
    key: 'time',
    label: $t('Laikas'),
    value: [meetingRelativeTime.value, meetingTimeLabel.value].filter(Boolean).join(' · '),
  },
  {
    key: 'institution',
    label: $t('Institucija'),
  },
  { key: 'people', label: $t('Atstovai') },
  {
    key: 'after-meeting',
    label: $t('meetings.record.after_meeting'),
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
      openAgendaSheet(urlAction === 'add-bulk' ? 'paste' : 'lines');
      if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        window.history.replaceState({}, '', url.toString());
      }
    }, 100);
  }
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
  });

  if (isJoint.value) {
    return `${datePart} jungtinis posėdis`;
  }

  const institutionName = typeof mainInstitution === 'string'
    ? mainInstitution
    : mainInstitution.name;

  return `${datePart} ${genitivizeEveryWord(institutionName)} posėdis`;
});

watch(() => props.meeting.id, () => enterMeeting(props.meeting), { immediate: true });

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
    openAgendaSheet('paste');
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
    if (props.completion.missingActions[0]?.type === 'agenda_missing') {
      handleMissingAction(props.completion.missingActions[0]);
      return;
    }
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
