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
      <div class="space-y-6">
        <MeetingAgendaList
          v-model:editing="agendaEditing"
          :agenda-items="meeting.agenda_items ?? []"
          :meeting-id="meeting.id"
          :can-add="abilities.createAgendaItems"
          :can-reorder="abilities.reorderAgendaItems"
          :requires-student-perspective="!isInternalBody"
          :loading-item-id="editableAgendaItemLoading"
          @add="openAgendaSheet"
          @edit="openAgendaItemEditor"
          @delete="requestAgendaItemDelete"
        />
        <AgendaItemEditSheet
          v-if="editableAgendaItem"
          :key="editableAgendaItemKey"
          v-model:open="agendaItemEditorOpen"
          :item="editableAgendaItem"
          :default-start-time="previousItemEnd(editableAgendaItem.order)"
          :meeting-start-time="isEmailMeeting(meeting) ? null : formatTime(meeting.start_time)"
          :requires-student-perspective="!isInternalBody"
          :is-public="isShownPublicly(publicVisibility)"
          :can-delete="agendaItemBeingEdited?.can?.delete ?? false"
          :except="['documents', 'tasks', 'pendingDocuments']"
          @delete="deleteFromEditor"
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
          :pending-documents
          :institution-ids
          :tenant-shortnames
          :can-update="abilities.update"
        />
      </Deferred>
    </template>

    <template #files>
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
    <MeetingForm v-model:open="showMeetingModal" :meeting />

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
            Retais atvejais, atstovavimo organai gali turėti bendrų posėdžių. Jei šis posėdis yra bendras su kitomis institucijomis, pasirink jas.
            Gali pasirinkti tik iš susijusių institucijų.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 pt-2">
          <Select v-model="addInstitutionId">
            <SelectTrigger>
              <SelectValue :placeholder="$t('Pasirink instituciją')" />
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
            {{ $t("Ar tikrai nori ištrinti šį darbotvarkės punktą? Šis veiksmas negrįžtamas.") }}
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
          <p data-testid="meeting-delete-description" class="text-sm text-muted-foreground">
            {{ abilities?.discard
              ? $t('Posėdis tuščias ir ką tik sukurtas, todėl bus ištrintas visam laikui.')
              : $t('Posėdis bus perkeltas į šiukšlinę, o jo užduotys pašalintos. Atkūrus posėdį, neužbaigta užduotis grįš.') }}
          </p>

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
import { useClipboard, useStorage } from '@vueuse/core';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, CalendarPlus, CalendarX, CircleCheck, CircleDashed, Copy, Edit, Globe, Link2, Plus, Trash2 } from '@lucide/vue';
import { DialogDescription } from 'reka-ui';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { preferredScrollBehavior } from '@/Composables/useAccessibilityPreferences';
import { useToasts } from '@/Composables/useToasts';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { InstitutionScope, LocaleEnum, ModelEnum, type ActionType, type AgendaItemType, type CalendarHeroStyleEnum, type CommentKind, type DocumentStatus, type MeetingKind, type MeetingType, type TenantType } from '@/Types/enums';
import { genitivizeEveryWord } from '@/Utils/String';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatMeetingDateTime, formatMeetingTimeOnly, isEmailMeeting } from '@/Utils/MeetingDisplay';
import { useMeetingUrgency } from '@/Composables/useMeetingUrgency';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statusIcons';
import RecordPage, { type RecordAction, type RecordFact, type RecordNavigationContext } from '@/Components/Layouts/RecordPage.vue';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import UsersFactList from '@/Components/Avatars/UsersFactList.vue';
import MeetingAgendaList from '@/Components/Meetings/MeetingAgendaList.vue';
import { useApi } from '@/Composables/useApi';
import type { EditableAgendaItemSource } from '@/Composables/useAgendaItemAutosave';
import { formatNearDate, formatTime } from '@/Utils/dateTime';
import AgendaItemEditSheet from '@/Components/AgendaItems/AgendaItemEditSheet.vue';
import MeetingCompletionChecklist, { type MeetingMissingAction } from '@/Components/Meetings/MeetingCompletionChecklist.vue';
import MeetingDatePlate from '@/Components/Meetings/MeetingDatePlate.vue';
import AddAgendaItemsSheet, { type AddAgendaMode, type RecentAgenda } from '@/Components/Meetings/AddAgendaItemsSheet.vue';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import AnnounceMeetingDialog from '@/Components/Meetings/AnnounceMeetingDialog.vue';
import MeetingPublicVisibilityDialog from '@/Components/Meetings/MeetingPublicVisibilityDialog.vue';
import { isShownPublicly, meetingVisibilityLabel, type MeetingPublicVisibility } from '@/Components/Meetings/meetingPublicVisibility';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import MeetingDocumentsPanel from '@/Components/Meetings/MeetingDocumentsPanel.vue';
import { FileableFilesPanel, type DocumentFolderRow } from '@/Components/Files';
import { MEETING_PRIMARY_FILE_TYPES, type FileableFileType } from '@/Constants/fileTypes';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { enterMeeting } from '@/Composables/useRecordTrail';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import { countIncompleteTasks } from '@/Composables/useTaskUrgency';

interface MeetingTenant {
  id: number;
  type: TenantType | null;
  fullname: string;
  shortname: string;
  alias: string;
  phone: string | null;
  email: string | null;
  address: string | null;
  shortname_vu: string | null;
  primary_institution_id: string | null;
  goals_enabled: boolean;
  created_at: string | null;
  updated_at: string | null;
}

interface MeetingInstitutionType {
  extra_attributes: Record<string, unknown> | unknown[] | null;
  id: number;
  parent_id: number | null;
  title: string | null;
  description: string | null;
  slug: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  pivot: { institution_id: string; institution_type_id: number };
}

interface MeetingInstitution {
  id: string;
  name: string | null;
  short_name: string | null;
  alias: string;
  description: string | null;
  address: string | null;
  phone: string | null;
  email: string | null;
  working_hours: string | null;
  website: string | null;
  logo_url: string | null;
  facebook_url: string | null;
  instagram_url: string | null;
  tenant_id: number | null;
  is_active: number;
  meeting_periodicity_days: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  types: Array<MeetingInstitutionType>;
  tenant: MeetingTenant | null;
  pivot: { meeting_id: string; institution_id: string };
}

interface MeetingImage {
  id: number | null;
  url: string;
  thumb: string;
  srcset: string | null;
  width: number | null;
  height: number | null;
  focal_point: string | null;
  alt: string | null;
  author: string | null;
}

interface MeetingMedia {
  id: number;
  model_type: string;
  model_id: string;
  uuid: string | null;
  collection_name: string;
  name: string;
  file_name: string;
  mime_type: string | null;
  disk: string;
  conversions_disk: string | null;
  size: number;
  manipulations: Record<string, unknown> | unknown[];
  custom_properties: Record<string, unknown> | unknown[];
  generated_conversions: Record<string, boolean>;
  responsive_images: Record<string, unknown> | unknown[];
  order_column: number | null;
  created_at: string | null;
  updated_at: string | null;
  original_url: string;
  preview_url: string;
}

interface MeetingCalendarEvent {
  id: number;
  title: string | null;
  permalink: string | null;
  description: string | null;
  location: string | null;
  is_remote: boolean;
  organizer: string | null;
  cto_url: string | null;
  facebook_url: string | null;
  main_image_focal_point: string | null;
  is_draft: boolean;
  is_all_day: boolean;
  is_international: boolean;
  hero_style: CalendarHeroStyleEnum;
  date: string;
  end_date: string | null;
  event_type_id: number | null;
  tenant_id: number;
  meeting_id: string | null;
  created_at: string;
  updated_at: string;
  registration_form_id: number | null;
  deleted_at: string | null;
  main_image_url: string | null;
  main_image_media: MeetingImage | null;
  media: Array<MeetingMedia>;
}

interface MeetingFile {
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

interface MeetingComment {
  metadata: Record<string, unknown> | unknown[] | null;
  mentioned_user_ids: string[] | null;
  id: string;
  parent_id: string | null;
  thread_root_id: string | null;
  commentable_type: string;
  commentable_id: string;
  user_id: string | null;
  kind: CommentKind;
  body: string;
  resolved_at: string | null;
  resolved_by: string | null;
  edited_at: string | null;
  erased_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  user: { id: string; name: string; profile_photo_path: string | null } | null;
}

interface MeetingVote {
  id: string;
  agenda_item_id: string;
  is_main: boolean;
  is_consensus: boolean;
  student_vote: string | null;
  decision: string | null;
  student_benefit: string | null;
  order: number;
  created_at: string | null;
  updated_at: string | null;
  title: string | null;
  note: string | null;
}

interface RedactedAgendaItem {
  id: string;
  order: number;
  is_private: true;
  is_redacted: true;
  title: string;
  can: { update: boolean; delete: boolean };
}

interface FullAgendaItem {
  tenants: MeetingTenant[] | null;
  meeting: Record<string, unknown> | null;
  is_redacted?: never;
  id: string;
  meeting_id: string;
  created_at: string;
  updated_at: string;
  order: number;
  brought_by_students: boolean;
  type: AgendaItemType | null;
  start_time: string | null;
  end_time: string | null;
  title: string | null;
  description: string | null;
  student_position: string | null;
  is_private: boolean;
  public_title: string | null;
  votes: Array<MeetingVote>;
  comments_count: number;
  has_notes: boolean;
  can: { update: boolean; delete: boolean };
}

type AgendaListItem = RedactedAgendaItem | FullAgendaItem;

interface MeetingRecord {
  tenants: Array<Record<string, unknown>> | null;
  type_label?: never;
  id: string;
  title: string;
  meeting_kind: MeetingKind | null;
  type: MeetingType | null;
  start_time: string;
  end_time: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  description: { lt: string; en: string };
  title_noun: string;
  inherited_meeting_kind: MeetingKind;
  agenda_items: AgendaListItem[];
  sharepointPath: string | null;
  sharepointFolderUrl: string | null;
  institutions: Array<MeetingInstitution>;
  calendar_event: MeetingCalendarEvent | null;
  is_public: boolean;
  is_joint: boolean;
  has_protocol: boolean;
  has_report: boolean;
}

interface InternalMeetingRecord extends MeetingRecord {
  comments_count: number;
  tasks_count: number;
  documents_count: number;
  fileable_files: Array<MeetingFile>;
  comments: Array<MeetingComment>;
}

interface MeetingRepresentativePivot {
  dutiable_type: string;
  dutiable_id: string;
  duty_id: string;
  id: string;
  via_dutiable_id: string | null;
  tenant_id: number | null;
  start_date: string;
  end_date: string | null;
  additional_photo: string | null;
  additional_photo_focal_point: string | null;
  additional_email: string | null;
  use_original_duty_name: boolean;
  description: string | null;
  study_program_id: string | null;
}

interface MeetingRepresentativeDuty {
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
  pivot: MeetingRepresentativePivot;
}

interface MeetingRepresentative {
  id: string;
  email: string | null;
  phone: string | null;
  facebook_url: string | null;
  name: string;
  pronouns: string | null;
  show_pronouns: boolean;
  is_active: number;
  updated_at: string;
  created_at: string;
  profile_photo_path: string | null;
  profile_photo_focal_point: string | null;
  deleted_at: string | null;
  duties: Array<MeetingRepresentativeDuty>;
}

interface MeetingTask {
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
  users: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}

interface AttachableMeetingInstitution {
  id: string;
  name: string | null;
  is_active: boolean;
  tenant_shortname: string | null;
}

interface MeetingSecretary {
  id: string;
  name: string;
  email: string | null;
  profile_photo_path: string | null;
}

type MeetingDocumentInstitution = Omit<MeetingInstitution, 'types' | 'pivot'>;

interface LinkedMeetingDocumentFields {
  id: number;
  name: string;
  title: string;
  document_date: string | null;
  institution_id: string | null;
  meeting_id: string | null;
  content_type: string | null;
  language: string | null;
  summary: string | null;
  anonymous_url: string | null;
  link_url: string | null;
  status: DocumentStatus;
  removed_from_sharepoint_at: string | null;
  published_at: string | null;
  sharepoint_modified_at: string | null;
  checked_at: string | null;
  sync_status: 'pending' | 'syncing' | 'success' | 'failed' | 'imported';
  sync_error_message: string | null;
  sync_attempts: number;
  last_sync_attempt_at: string | null;
  effective_date: string | null;
  expiration_date: string | null;
  language_code: string;
  institution: MeetingDocumentInstitution | null;
}

type LinkedMeetingDocument = LinkedMeetingDocumentFields | (LinkedMeetingDocumentFields & { tenant: Array<Record<string, unknown>> });

interface MeetingAbilities {
  update: boolean;
  delete: boolean;
  discard: boolean;
  createAgendaItems: boolean;
  reorderAgendaItems: boolean;
  attachInstitution: boolean;
}

interface MeetingCompletion {
  status: MeetingCompletionStatus | null;
  missingActions: MeetingMissingAction[];
}

const props = withDefaults(defineProps<{
  meeting: MeetingRecord | InternalMeetingRecord;
  representatives: MeetingRepresentative[];
  secretaries: MeetingSecretary[];
  availableInstitutionsForAttach: AttachableMeetingInstitution[];
  governanceScope?: string;
  abilities: MeetingAbilities;
  completion: MeetingCompletion;
  publicVisibility: MeetingPublicVisibility;
  recordNavigation?: RecordNavigationContext;
  tasks?: MeetingTask[];
  documents?: LinkedMeetingDocument[];
  /** Unpublished SharePoint files this meeting could link. */
  pendingDocuments?: DocumentFolderRow[];
  recentAgendas?: RecentAgenda[] | null;
  files: MeetingFile[];
  /** A public meeting outside the user's reach: its agenda only (MeetingPolicy::viewSummary). */
  readOnly: boolean;
}>(), {
  availableInstitutionsForAttach: () => [],
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
  pendingDocuments: () => [],
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

// The edit sheet loads every translation when opened.

const agendaItemEditorOpen = ref(false);
const agendaItemBeingEdited = ref<AgendaListItem | null>(null);
const editableAgendaItem = ref<(EditableAgendaItemSource & { id: string; order: number }) | null>(null);
const editableAgendaItemKey = ref(0);
const editableAgendaItemLoading = ref<string | null>(null);
const editableAgendaItemUrl = ref('');
const editableAgendaItemApi = useApi<EditableAgendaItemSource & { id: string; order: number }>(editableAgendaItemUrl, { immediate: false, refetch: false });

const openAgendaItemEditor = async ({ id }: Pick<AgendaListItem, 'id'>) => {
  const item = props.meeting.agenda_items.find(candidate => candidate.id === id);
  if (!item) return;

  editableAgendaItemLoading.value = item.id;
  editableAgendaItemUrl.value = route('api.v1.admin.agendaItems.editable', item.id);
  await editableAgendaItemApi.execute();

  if (editableAgendaItemLoading.value !== item.id) {
    return;
  }
  editableAgendaItemLoading.value = null;

  if (editableAgendaItemApi.isSuccess.value && editableAgendaItemApi.data.value) {
    agendaItemBeingEdited.value = item;
    editableAgendaItem.value = editableAgendaItemApi.data.value;
    editableAgendaItemKey.value++;
    agendaItemEditorOpen.value = true;
  }
};

const deleteFromEditor = () => {
  agendaItemEditorOpen.value = false;
  if (agendaItemBeingEdited.value) {
    requestAgendaItemDelete(agendaItemBeingEdited.value);
  }
};

const previousItemEnd = (order: number): string | null => {
  const previous = (props.meeting.agenda_items ?? [])
    .filter((item): item is FullAgendaItem => !item.is_redacted && item.order < order && !!item.end_time)
    .sort((a, b) => b.order - a.order)[0];

  return previous?.end_time ? String(previous.end_time).slice(0, 5) : null;
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
const addInstitutionId = ref('');

const availableInstitutionsToAdd = computed(() => {
  const attached = new Set(props.meeting.institutions?.map((i) => i.id) ?? []);
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
  // Institutions decide which pending documents can be linked, and who the tasks reach.
  router.post(route('meetings.institutions.attach', props.meeting.id), {
    institution_id: addInstitutionId.value,
  }, {
    preserveScroll: true,
    except: ['documents'],
    onSuccess: () => {
      showAddInstitutionDialog.value = false;
      addInstitutionId.value = '';
    },
  });
};

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

// Event handlers
// Agenda item deletion goes through a confirmation dialog
const showAgendaItemDeleteDialog = ref(false);
const agendaItemPendingDelete = ref<AgendaListItem | null>(null);

const requestAgendaItemDelete = (agendaItem: AgendaListItem) => {
  agendaItemPendingDelete.value = agendaItem;
  showAgendaItemDeleteDialog.value = true;
};

const confirmAgendaItemDelete = () => {
  if (!agendaItemPendingDelete.value) {
    return;
  }
  router.delete(route('agendaItems.destroy', agendaItemPendingDelete.value.id), {
    preserveScroll: true,
    except: ['documents', 'pendingDocuments'],
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
