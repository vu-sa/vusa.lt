<template>
  <RecordPage
    v-model:section="currentTab"
    section-query-key="tab"
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
          <InactiveInstitutionBadge :active="institution.is_active" />
        </template>
      </div>
      <span v-else>{{ $t('Be institucijos') }}</span>
    </template>

    <template #fact-people>
      <UsersFactList :users="representatives" :inline-limit="1" />
    </template>

    <template #fact-visibility>
      <div class="flex flex-wrap items-center gap-x-2">
        <a v-if="publicVisibility.url" :href="publicVisibility.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-status-success underline underline-offset-4">
          {{ meetingVisibilityLabel(publicVisibility) }}
        </a>
        <span v-else>{{ meetingVisibilityLabel(publicVisibility) }}</span>
        <SpotlightPopover
          v-if="!readOnly"
          :title="$t('meetings.visibility.spotlight_title')"
          :description="$t('meetings.visibility.spotlight_body')"
          :is-dismissed="visibilitySpotlight.isDismissed.value"
          @dismiss="visibilitySpotlight.dismiss()"
        >
          <button
            type="button"
            class="text-xs font-normal text-muted-foreground underline decoration-border underline-offset-4 hover:text-foreground hover:decoration-foreground pointer-coarse:min-h-11"
            data-testid="meeting-visibility-explain"
            @click="openVisibilityDialog"
          >
            {{ $t('meetings.visibility.trigger') }}
          </button>
        </SpotlightPopover>
      </div>
      <MeetingPublicVisibilityDialog v-model:open="showVisibilityDialog" :mode="publicVisibility.mode" />
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
      <MeetingAgendaPanel
        v-model:editing="agendaEditing"
        :meeting
        :can-add="abilities.createAgendaItems"
        :can-reorder="abilities.reorderAgendaItems"
        :requires-student-perspective="!isInternalBody"
        :is-public="isShownPublicly(publicVisibility)"
        @add="openAgendaSheet"
      />
    </template>

    <template #documents>
      <WhenVisible v-if="!readOnly" :data="['documents', 'pendingDocuments']">
        <template #fallback>
          <div class="space-y-3">
            <Skeleton class="h-10 w-full" />
            <Skeleton class="h-24 w-full" />
          </div>
        </template>
        <MeetingDocumentsPanel
          :meeting-id="meeting.id"
          :documents="documents ?? []"
          :pending-documents
          :institution-ids
          :tenant-shortnames
          :can-update="abilities.update"
        />
      </WhenVisible>
    </template>

    <template #files>
      <WhenVisible v-if="!readOnly" data="files">
        <template #fallback>
          <div class="space-y-3">
            <Skeleton class="h-10 w-full" />
            <Skeleton class="h-24 w-full" />
          </div>
        </template>
        <FileableFilesPanel
          ref="filesPanel"
          :fileable="{ id: meeting.id, type: 'Meeting' }"
          :files
          :can-upload="canUploadFiles"
          :folder-url="meeting.sharepointFolderUrl"
          :can-delete="abilities.update"
          :primary-types="MEETING_PRIMARY_FILE_TYPES"
          :default-date="meeting.start_time"
        />
      </WhenVisible>
    </template>

    <template #tasks>
      <WhenVisible v-if="!readOnly" data="tasks">
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
      </WhenVisible>
    </template>

    <template v-if="!readOnly" #activity>
      <RecordActivity subject-type="meeting" :subject-id="meeting.id" commentable-type="meeting" :commentable-id="meeting.id" />
    </template>

    <!-- Modals -->
    <MeetingForm v-model:open="showMeetingModal" :meeting />

    <AddAgendaItemsSheet
      v-model:open="showAgendaSheet"
      :meeting-id="meeting.id"
      :initial-mode="agendaSheetMode"
      :recent-agendas
    />

    <AttachMeetingInstitutionDialog
      v-model:open="showAddInstitutionDialog"
      :meeting-id="meeting.id"
      :attached-ids="institutionIds"
      :available-institutions="availableInstitutionsForAttach"
    />

    <ConfirmDialog
      v-model:open="showDeleteDialog"
      :title="$t('Šalinti posėdį?')"
      :description="abilities?.discard
        ? $t('Posėdis tuščias ir ką tik sukurtas, todėl bus ištrintas visam laikui.')
        : $t('Posėdis bus perkeltas į šiukšlinę, o jo užduotys pašalintos. Atkūrus posėdį, neužbaigta užduotis grįš.')"
      :confirm-label="$t('Šalinti posėdį')"
      destructive
      @confirm="handleMeetingDelete"
    />
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

<script setup lang="ts">
import { ref, computed, watch, onMounted, defineAsyncComponent, nextTick } from 'vue';
import { Link, router, WhenVisible } from '@inertiajs/vue3';
import { useClipboard, useStorage } from '@vueuse/core';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { CalendarPlus, CalendarX, CircleCheck, CircleDashed, Copy, Edit, Globe, Link2, Plus, Trash2 } from '@lucide/vue';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { preferredScrollBehavior } from '@/Composables/useAccessibilityPreferences';
import { useToasts } from '@/Composables/useToasts';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { InstitutionScope, LocaleEnum, ModelEnum } from '@/Types/enums';
import type { AttachableMeetingInstitution, LinkedMeetingDocument, MeetingAbilities, MeetingCompletion, MeetingFile, MeetingInstitution, MeetingRecord, MeetingRepresentative } from '@/Types/MeetingPage';
import type { TaskRow } from '@/Types/TaskRow';
import { genitivizeEveryWord } from '@/Utils/String';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatMeetingDateTime, formatMeetingTimeOnly } from '@/Utils/MeetingDisplay';
import { useMeetingUrgency } from '@/Composables/useMeetingUrgency';
import { meetingCompletionStatuses } from '@/Constants/statusIcons';
import RecordPage, { type RecordAction, type RecordFact, type RecordNavigationContext } from '@/Components/Layouts/RecordPage.vue';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import UsersFactList from '@/Components/Avatars/UsersFactList.vue';
import MeetingAgendaPanel from '@/Components/Meetings/MeetingAgendaPanel.vue';
import AttachMeetingInstitutionDialog from '@/Components/Meetings/AttachMeetingInstitutionDialog.vue';
import { useOptionalPanelProps } from '@/Composables/useOptionalPanelProps';
import { formatNearDate } from '@/Utils/dateTime';
import MeetingCompletionChecklist, { type MeetingMissingAction } from '@/Components/Meetings/MeetingCompletionChecklist.vue';
import MeetingDatePlate from '@/Components/Meetings/MeetingDatePlate.vue';
import AddAgendaItemsSheet, { type AddAgendaMode, type RecentAgenda } from '@/Components/Meetings/AddAgendaItemsSheet.vue';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import AnnounceMeetingDialog from '@/Components/Meetings/AnnounceMeetingDialog.vue';
import MeetingPublicVisibilityDialog from '@/Components/Meetings/MeetingPublicVisibilityDialog.vue';
import { isShownPublicly, meetingVisibilityLabel, type MeetingPublicVisibility } from '@/Components/Meetings/meetingPublicVisibility';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import SpotlightPopover from '@/Components/Patterns/SpotlightPopover.vue';
import MeetingDocumentsPanel from '@/Components/Meetings/MeetingDocumentsPanel.vue';
import { FileableFilesPanel, type DocumentFolderRow } from '@/Components/Files';
import { MEETING_PRIMARY_FILE_TYPES, type FileableFileType } from '@/Constants/fileTypes';
import RecordActivity from '@/Components/ActivityLog/RecordActivity.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { enterMeeting } from '@/Composables/useRecordTrail';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import { countIncompleteTasks } from '@/Composables/useTaskUrgency';

const props = withDefaults(defineProps<{
  meeting: MeetingRecord;
  representatives: MeetingRepresentative[];
  availableInstitutionsForAttach?: AttachableMeetingInstitution[];
  governanceScope?: string;
  abilities: MeetingAbilities;
  completion: MeetingCompletion;
  publicVisibility: MeetingPublicVisibility;
  recordNavigation?: RecordNavigationContext;
  tasks?: TaskRow[];
  documents?: LinkedMeetingDocument[];
  /** Unpublished SharePoint files this meeting could link. */
  pendingDocuments?: DocumentFolderRow[];
  recentAgendas?: RecentAgenda[] | null;
  files?: MeetingFile[];
  incompleteTasksCount: number;
  /** A public meeting outside the user's reach: its agenda only (MeetingPolicy::viewSummary). */
  readOnly: boolean;
}>(), {
  availableInstitutionsForAttach: undefined,
  governanceScope: undefined,
  abilities: () => ({
    update: false,
    delete: false,
    discard: false,
    createAgendaItems: false,
    reorderAgendaItems: false,
    attachInstitution: false,
  }),
  completion: () => ({ status: 'no_items', missingActions: [] }),
  publicVisibility: () => ({ mode: 'internal', url: null }),
  recordNavigation: undefined,
  tasks: undefined,
  documents: undefined,
  pendingDocuments: undefined,
  recentAgendas: undefined,
  files: undefined,
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
const calendarEvent = computed(() => props.meeting.has_calendar_event);

const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

const {
  showTaskDetail,
  selectedDetailTask,
  openTaskDetail,
  closeTaskDetail,
  reportFromDetail,
} = useTaskActionDialogs();

const showAnnounceDialog = ref(false);

const showVisibilityDialog = ref(false);
const visibilitySpotlight = useFeatureSpotlight('meeting-public-visibility-v1');

const openVisibilityDialog = () => {
  visibilitySpotlight.dismiss();
  showVisibilityDialog.value = true;
};

const openAnnounceDialog = () => {
  showAnnounceDialog.value = true;
};

const handleUnlinkCalendarEvent = () => {
  router.delete(route('meetings.calendarEvent.destroy', { meeting: props.meeting.id }), {
    preserveScroll: true,
    except: ['documents', 'tasks', 'pendingDocuments'],
  });
};

// File availability is shown as facts, not completion requirements.
const { hasProtocol, isPastMeeting } = useMeetingUrgency(() => props.meeting);

// Hide HH:MM for email/electronic meetings (start_time is forced to 23:59 as a deadline marker)
const meetingTimeLabel = computed(() => formatMeetingTimeOnly(props.meeting));
const meetingRelativeTime = computed(() => formatNearDate(props.meeting.start_time));
const meetingStatus = computed(() => props.completion.status === null ? undefined : meetingCompletionStatuses[props.completion.status]);
const documentStatuses = computed(() => [
  {
    key: 'protocol',
    fileType: 'Protokolai' as FileableFileType,
    label: $t('meetings.record.protocol'),
    uploaded: hasProtocol.value,
    state: hasProtocol.value ? $t('Įkeltas') : $t('Neįkeltas'),
  },
]);

// The folder needs an institution with a padalinys; without one there is nowhere to upload.
const canUploadFiles = computed(() => props.abilities.update && !!props.meeting.sharepointPath);
const filesPanel = ref<InstanceType<typeof FileableFilesPanel> | null>(null);

const pendingUploadType = ref<FileableFileType | null>(null);
const openFilesUpload = async (type: FileableFileType) => {
  pendingUploadType.value = type;
  currentTab.value = 'files';
  loadOptionalProps(['files']);
  await nextTick();
  openPendingUpload();
};
const openPendingUpload = () => {
  if (pendingUploadType.value && filesPanel.value) {
    filesPanel.value.openUpload(pendingUploadType.value);
    pendingUploadType.value = null;
  }
};
watch(filesPanel, openPendingUpload, { flush: 'post' });

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
  { value: 'tasks', label: $t('Užduotys'), count: props.tasks === undefined ? props.incompleteTasksCount : countIncompleteTasks(props.tasks) },
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

const { load: loadOptionalProps } = useOptionalPanelProps({
  keys: ['files', 'tasks', 'documents', 'pendingDocuments', 'availableInstitutionsForAttach'] as const,
  isLoaded: key => props[key] !== undefined,
  alwaysExcept: ['recentAgendas'],
  disabled: () => props.readOnly,
  recordKey: () => props.meeting.id,
});

watch(currentTab, (newTab) => {
  storedTab.value = newTab;
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
    surfaceClass: isShownPublicly(props.publicVisibility) ? 'bg-status-success-surface' : 'bg-status-neutral-surface',
    value: meetingVisibilityLabel(props.publicVisibility),
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

  if (props.abilities.attachInstitution) {
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

  if (urlAction === 'edit' && props.abilities.update) {
    showMeetingModal.value = true;
    const url = new URL(window.location.href);
    url.searchParams.delete('action');
    window.history.replaceState(window.history.state, '', url.toString());
  }

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
const mainInstitution: MeetingInstitution | string
  = props.meeting.institutions?.[0] ?? 'Be institucijos';

const meetingTitle = computed(() => {
  const locale = getActiveLanguage() === 'en' ? LocaleEnum.EN : LocaleEnum.LT;
  const datePart = formatMeetingDateTime(props.meeting, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }, locale);
  const noun = props.meeting.title_noun ?? $t('posėdis');

  if ((props.meeting.institutions?.length ?? 0) > 1) return `${datePart} ${noun}`;

  const institution = props.meeting.institutions?.[0];
  const name = getTranslatedValue(institution?.name, locale);
  const institutionName = locale === LocaleEnum.LT ? genitivizeEveryWord(name) : name;

  return [datePart, institutionName, noun].filter(Boolean).join(' ');
});

watch(() => props.meeting.id, () => enterMeeting({
  ...props.meeting,
  institutions: props.meeting.institutions.map(institution => ({ id: institution.id, name: institution.name ?? '' })),
}), { immediate: true });

// Joint meeting — institution management
const showAddInstitutionDialog = ref(false);
watch(showAddInstitutionDialog, (open) => {
  if (open) loadOptionalProps(['availableInstitutionsForAttach']);
});

const handleDetachInstitution = (institutionId: string) => {
  router.delete(route('meetings.institutions.detach', { meeting: props.meeting.id, institution: institutionId }), {
    preserveScroll: true,
    except: ['documents'],
  });
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

const toasts = useToasts();
const { copy: copyUrl } = useClipboard({ legacy: true });

const handleRecordAction = (action: string) => {
  if (action === 'complete') {
    if (props.completion.missingActions[0]?.type === 'agenda_missing') {
      handleMissingAction(props.completion.missingActions[0]);
      return;
    }
    document.getElementById('meeting-completion')?.scrollIntoView({ behavior: preferredScrollBehavior(), block: 'center' });
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
    void copyUrl(window.location.href).then(() => {
      toasts.success($t('common.link_copied'));
    });
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

const handleMeetingDelete = () => {
  const redirectTo = typeof mainInstitution === 'string'
    ? route('admin.dashboard')
    : route('institutions.show', mainInstitution.id);

  router.delete(route('meetings.destroy', props.meeting.id), {
    data: { redirect_to: redirectTo },
  });
};
</script>
