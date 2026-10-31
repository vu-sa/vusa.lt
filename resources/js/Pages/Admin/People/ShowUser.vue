<template>
  <RecordPage
    v-model:section="currentSection"
    :title="user.name"
    :entity-type="ModelEnum.USER"
    :history-subject="{ type: 'user', id: String(user.id) }"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    @action="handleRecordAction"
  >
    <template #identity>
      <UserAvatar :user size="xl" border class="size-12 shrink-0 sm:size-14" />
    </template>

    <template #subtitle>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
        <span v-if="subtitle">{{ subtitle }}</span>
        <span v-if="pronounsBadge" class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium">{{ pronounsBadge }}</span>
      </div>
    </template>

    <template #fact-email>
      <a v-if="user.email" :href="`mailto:${user.email}`" class="underline underline-offset-4">{{ user.email }}</a>
      <span v-else>—</span>
    </template>

    <template #fact-phone>
      <a v-if="user.phone" :href="`tel:${user.phone}`" class="tabular-nums underline underline-offset-4">{{ user.phone }}</a>
      <span v-else>—</span>
    </template>

    <!-- The same hover list as the members collection: where they serve now and served before. -->
    <template #fact-years>
      <MemberTermsSummary :duties="allTerms">
        <span class="font-medium text-foreground">{{ yearSpan }}</span>
      </MemberTermsSummary>
    </template>

    <template #alert>
      <!-- Shown once: the server flashes the password on the redirect and never keeps it. -->
      <div
        v-if="generatedPassword"
        class="space-y-2 border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-4"
        data-testid="generated-password"
      >
        <p class="text-sm font-semibold text-foreground">
          {{ $t('Sugeneruotas slaptažodis:') }}
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <Input readonly :model-value="generatedPassword" class="max-w-xs font-mono" />
          <Button type="button" variant="outline" size="sm" class="pointer-coarse:h-11" @click="copyPassword">
            <Copy class="size-4" aria-hidden="true" />
            {{ hasCopied ? $t('Nukopijuota!') : $t('Kopijuoti') }}
          </Button>
        </div>
        <p class="text-xs text-muted-foreground">
          {{ $t('Šis slaptažodis bus rodomas tik vieną kartą! Įsitikink, kad jį išsaugojai saugiai.') }}
        </p>
      </div>
    </template>

    <template #duties>
      <div v-if="allDuties.length" class="grid gap-10 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] xl:gap-16">
        <div v-for="column in dutyColumns" :key="column.key" class="flex min-w-0 flex-col gap-10">
          <OverviewSection
            v-for="group in column.groups"
            :key="group.key"
            variant="home"
            :title="group.title"
            :icon="group.icon"
            :count="group.duties.length"
            :data-group="group.key"
          >
            <div class="divide-y divide-border">
              <UserTermRow
                v-for="{ duty, nested, sourceLabel } in group.rows"
                :key="`${duty.id}-${duty.pivot?.id}`"
                :duty
                :nested
                :source-label
                :holder="dutyHolder"
                :can-manage="Boolean(can?.update) && group.key !== 'previous'"
                @edit="openTermSheet(duty)"
                @end="openTermSheet(duty)"
              />
            </div>
          </OverviewSection>
        </div>
      </div>

      <EmptyState
        v-else
        :title="$t('Pareigų nėra')"
        :description="$t('Šiam nariui dar nėra priskirta pareigybių.')"
        :icon="Briefcase"
        :action-label="can?.update ? $t('Pridėti pareigybę') : undefined"
        @action="openAssignSheet"
      />
    </template>

    <template #access>
      <OverviewSection variant="home" :title="$t('users.access.title')" :icon="Shield" class="max-w-2xl">
        <div class="border-y border-border">
          <FormToggleRow
            :model-value="isSuperAdmin"
            :label="$t('Super Admin')"
            :hint="$t('users.access.super_admin_hint')"
            :disabled="!can?.updateSuperAdmin"
            data-testid="super-admin-toggle"
            @update:model-value="superAdminTarget = $event"
          />
        </div>
        <p class="text-sm text-muted-foreground">
          {{ $t('users.access.from_duties') }}
        </p>
      </OverviewSection>
    </template>

    <template #activityRequests>
      <Deferred data="activityRequests">
        <template #fallback>
          <CollectionSkeleton :rows="3" />
        </template>
        <UserActivityRequestHistory v-if="activityRequests" :key="user.id" :history="activityRequests" />
      </Deferred>
    </template>

    <template #tasks>
      <TaskManager
        :tasks
        :task-stats
        :disabled="false"
        @open-task-detail="openTaskDetail"
      />
    </template>
  </RecordPage>

  <AssignDutyUserSheet
    v-model:open="assignSheetOpen"
    :duty="sheetDuty"
    :dutiable="sheetDutiable"
    :user="sheetUser"
    :study-programs="assignment?.studyPrograms ?? []"
  />

  <ConfirmDialog
    :open="superAdminTarget !== null"
    :title="$t(superAdminTarget ? 'users.access.grant_title' : 'users.access.revoke_title')"
    :description="$t(superAdminTarget ? 'users.access.grant_description' : 'users.access.revoke_description', { name: user.name })"
    :confirm-label="$t(superAdminTarget ? 'users.access.grant' : 'users.access.revoke')"
    :destructive="superAdminTarget === false"
    @update:open="!$event && (superAdminTarget = null)"
    @confirm="saveSuperAdmin"
  />

  <AccessChangeWarningDialog
    :open="accessWarningOpen"
    :report="accessWarningReport"
    @update:open="accessWarningOpen = $event"
    @confirm="accessWarningConfirm"
    @cancel="accessWarningCancel"
  />

  <ConfirmDialog
    v-model:open="generateOpen"
    :title="$t('Ar tikrai nori sugeneruoti naują slaptažodį šiam naudotojui?')"
    :description="user.has_password ? $t('Dėmesio: Tai pakeis esamą naudotojo slaptažodį!') : undefined"
    :confirm-label="$t('Generuoti')"
    @confirm="generatePassword"
  />
  <ConfirmDialog
    v-model:open="deletePasswordOpen"
    :title="$t('Ar tikrai nori ištrinti šio naudotojo slaptažodį?')"
    :description="$t('Dėmesio: Naudotojas nebegalės prisijungti su slaptažodžiu!')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="deletePassword"
  />
  <ConfirmDialog
    v-model:open="deleteUserOpen"
    :title="$t('Ištrinti narį?')"
    :description="$t('Profilis bus perkeltas į šiukšlinę.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="deleteUser"
  />

  <DutiableTimelineDialog v-model:open="timelineOpen" scope-type="user" :scope-id="user.id" />

  <TaskDetailDialog
    v-if="selectedDetailTask"
    :open="showTaskDetail"
    :task="selectedDetailTask"
    @close="closeTaskDetail"
    @report="reportFromDetail"
  />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue';
import { Deferred, router, usePage } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Briefcase, CalendarClock, CalendarRange, Copy, Edit3, History, KeyRound, Plus, Shield, Trash2 } from '@lucide/vue';

import UserActivityRequestHistory, { type UserActivityRequestHistory as RequestHistory } from '@/Components/Institutions/UserActivityRequestHistory.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { AdminRecordAction as ActionDescriptor } from '@/Features/Admin/RecordActions/types';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { CollectionSkeleton, ConfirmDialog, EmptyState, FormToggleRow, OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { DutiableTimelineDialog } from '@/Features/Admin/DutiableTimeline';
import { AssignDutyUserSheet, MemberTermsSummary, UserTermRow, termStatus, termYearSpan } from '@/Features/Admin/Occupancy';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { ModelEnum } from '@/Types/enums';
import { changeDutyNameEndings } from '@/Utils/String';
import { formatStaticTime } from '@/Utils/IntlTime';
import { todayIso } from '@/Utils/dateTime';

const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

interface Pivot { id?: string; via_dutiable_id?: string | null; tenant_id?: number | null; start_date?: string; end_date?: string | null; additional_email?: string | null; use_original_duty_name?: boolean }
type UserDuty = App.Entities.Duty & { pivot?: Pivot };

const props = defineProps<{
  user: App.Entities.User & {
    current_duties: UserDuty[];
    upcoming_duties: UserDuty[];
    previous_duties: UserDuty[];
    has_password: boolean;
  };
  tasks: Array<{
    id: string;
    name: string;
    description?: string | null;
    due_date?: string | null;
    completed_at?: string | null;
    created_at: string;
    action_type?: string | null;
    metadata?: Record<string, unknown>;
    progress?: { items_completed: number; items_total: number } | null;
    is_overdue?: boolean;
    can_be_manually_completed?: boolean;
    icon?: string;
    color?: string;
    taskable?: { id: string; name?: string | null; type?: string } | null;
    taskable_type: string;
    taskable_id: string;
    users?: Array<{ id: string; name: string; profile_photo_path?: string | null }>;
  }>;
  taskStats: { total: number; completed: number; pending: number; overdue: number; autoCompleting: number };
  /** From the controller, not `auth.can`: the flat map holds index/create/forceDelete only. */
  activityRequests?: RequestHistory;
  can?: { viewActivityRequests?: boolean; update: boolean; delete?: boolean; updateSuperAdmin?: boolean; managePasswords?: boolean };
  isSuperAdmin?: boolean;
  /** Deferred (`userPanels`): the Priskirti sheet's options, sent only to someone who may act on it. */
  assignment?: { studyPrograms: (App.Entities.StudyProgram & { tenant_id?: number | null })[] } | null;
}>();

// --- Sections and key facts -------------------------------------------------------------------

const currentSection = ref('duties');

const allTerms = computed(() => [
  ...(props.user.current_duties ?? []),
  ...(props.user.upcoming_duties ?? []),
  ...(props.user.previous_duties ?? []),
]);
const allDuties = allTerms;

function attachedTerms(duties: UserDuty[], previous: boolean) {
  const ids = new Set(duties.map(duty => duty.pivot?.id).filter(Boolean));
  const children = new Map<string, UserDuty[]>();
  const roots: UserDuty[] = [];
  for (const duty of duties) {
    const parentId = duty.pivot?.via_dutiable_id;
    if (parentId && ids.has(parentId)) {
      children.set(parentId, [...(children.get(parentId) ?? []), duty]);
    }
    else {
      roots.push(duty);
    }
  }
  const byDate = (a: UserDuty, b: UserDuty) => {
    const order = (a.pivot?.start_date ?? '').localeCompare(b.pivot?.start_date ?? '');
    return previous ? -order : order;
  };
  return roots.sort(byDate).flatMap((duty) => {
    const parentId = duty.pivot?.via_dutiable_id;
    const source = parentId ? allTerms.value.find(term => term.pivot?.id === parentId) : undefined;
    const sourceLabel = source
      ? changeDutyNameEndings(props.user, source.name, usePage().props.app.locale,
          dutyHolder.value.pronouns, source.pivot?.use_original_duty_name ?? false)
      : undefined;
    return [
      { duty, nested: false, sourceLabel },
      ...(children.get(duty.pivot?.id ?? '') ?? []).sort(byDate).map(child => ({
        duty: child, nested: true, sourceLabel: undefined,
      })),
    ];
  });
}

const dutyGroups = computed(() => {
  const at = todayIso();
  const groups = {
    current: [] as UserDuty[],
    upcoming: [] as UserDuty[],
    previous: [] as UserDuty[],
  };

  allTerms.value.forEach((duty) => {
    const status = termStatus(duty.pivot ?? {}, at);

    groups[status === 'ended' ? 'previous' : status].push(duty);
  });

  return [
    { key: 'current', title: $t('Dabartinės pareigos'), icon: Briefcase, duties: groups.current },
    { key: 'upcoming', title: $t('Būsimos pareigos'), icon: CalendarClock, duties: groups.upcoming },
    { key: 'previous', title: $t('Buvusios pareigos'), icon: History, duties: groups.previous },
  ].filter(group => group.duties.length > 0).map(group => ({
    ...group,
    rows: attachedTerms(group.duties, group.key === 'previous'),
  }));
});

/** What is held now (and next) leads; the record of past terms sits beside it on wide screens. */
const dutyColumns = computed(() => [
  { key: 'active', groups: dutyGroups.value.filter(group => group.key !== 'previous') },
  { key: 'previous', groups: dutyGroups.value.filter(group => group.key === 'previous') },
].filter(column => column.groups.length > 0));

const currentDuties = computed(() => dutyGroups.value.find(group => group.key === 'current')?.duties ?? []);
const yearSpan = computed(() => termYearSpan(allTerms.value.map(duty => duty.pivot ?? {}), $t('dabar')));
const showAccessSection = computed(() => Boolean(props.isSuperAdmin || props.can?.updateSuperAdmin));

const tabs = computed<RecordPageSection[]>(() => [
  { value: 'duties', label: $t('Pareigos'), count: allDuties.value.length },
  ...(showAccessSection.value ? [{ value: 'access', label: $t('users.access.title') }] : []),
  // `pending`, not `total`: a finished task is not something the reader still has to act on.
  ...(props.can?.viewActivityRequests ? [{ value: 'activityRequests', label: $t('activity_requests.personal.record_title') }] : []),
  { value: 'tasks', label: $t('Užduotys'), count: props.taskStats.pending },
]);

const recordFacts = computed<RecordFact[]>(() => {
  const facts: RecordFact[] = [
    { key: 'email', label: $t('El. paštas'), value: props.user.email ?? '—' },
  ];

  if (props.user.phone) {
    facts.push({ key: 'phone', label: $t('Telefonas'), value: props.user.phone });
  }

  if (props.user.facebook_url) {
    facts.push({ key: 'facebook', label: 'Facebook', value: $t('Facebook'), href: props.user.facebook_url });
  }

  if (yearSpan.value) {
    facts.push({ key: 'years', label: $t('Veiklos metai'), value: yearSpan.value });
  }

  if (props.user.last_action) {
    facts.push({ key: 'last_action', label: $t('Paskutinį kartą prisijungė'), value: formatStaticTime(props.user.last_action) });
  }

  return facts;
});

// --- Title band -------------------------------------------------------------------------------

const pronounsBadge = computed(() => {
  if (!props.user.show_pronouns || !props.user.pronouns) {
    return null;
  }

  const { pronouns } = props.user;

  return typeof pronouns === 'string' ? pronouns : (getTranslatedValue(pronouns) || null);
});

const dutyHolder = computed(() => ({ name: props.user.name, pronouns: props.user.pronouns }));

/** The headline role, not the contacts — those are key facts, labelled and actionable. */
const subtitle = computed(() => {
  const primary = currentDuties.value[0];

  if (!primary) {
    return undefined;
  }

  const { locale } = usePage().props.app;

  return changeDutyNameEndings(props.user, primary.name, locale, dutyHolder.value.pronouns, primary.pivot?.use_original_duty_name ?? false);
});

// --- Actions ----------------------------------------------------------------------------------

const canEdit = computed(() => props.can?.update ?? false);
const canDelete = computed(() => props.can?.delete ?? false);
const canManagePasswords = computed(() => props.can?.managePasswords ?? false);

const primaryAction = computed<ActionDescriptor | undefined>(() =>
  canEdit.value ? { key: 'assign', label: $t('Pridėti pareigybę'), icon: Plus } : undefined);

const overflowActions = computed<ActionDescriptor[]>(() => {
  const actions: ActionDescriptor[] = [];

  if (canEdit.value) {
    actions.push({ key: 'edit', label: $t('Redaguoti'), icon: Edit3 });
    actions.push({ key: 'timeline', label: $t('dutiables.timeline.open'), icon: CalendarRange });
  }

  if (canManagePasswords.value) {
    actions.push({ key: 'generate-password', label: $t('Generuoti naują slaptažodį'), icon: KeyRound });

    if (props.user.has_password) {
      actions.push({ key: 'delete-password', label: $t('Ištrinti slaptažodį'), icon: Trash2, destructive: true });
    }
  }

  if (canDelete.value) {
    actions.push({ key: 'delete', label: $t('Ištrinti narį'), icon: Trash2, destructive: true });
  }

  return actions;
});

const handleRecordAction = (key: string) => {
  switch (key) {
    case 'assign':
      openAssignSheet();
      break;
    case 'edit':
      router.get(route('users.edit', props.user.id));
      break;
    case 'timeline':
      timelineOpen.value = true;
      break;
    case 'generate-password':
      generateOpen.value = true;
      break;
    case 'delete-password':
      deletePasswordOpen.value = true;
      break;
    case 'delete':
      deleteUserOpen.value = true;
      break;
  }
};

const timelineOpen = ref(false);
const deleteUserOpen = ref(false);
const generateOpen = ref(false);
const deletePasswordOpen = ref(false);

const deleteUser = () => router.delete(route('users.destroy', props.user.id));

const generatePassword = () => router.post(route('users.generatePassword', props.user.id), {}, { preserveState: true, preserveScroll: true });

const deletePassword = () => router.delete(route('users.deletePassword', props.user.id), { preserveState: true, preserveScroll: true });

// The server flashes the generated password on the redirect back to this page.
const generatedPassword = computed(() => (usePage().props.flash as { data?: string } | undefined)?.data ?? null);
const { copy: copyToClipboard, copied: hasCopied } = useClipboard({ legacy: true, copiedDuring: 2000 });

const copyPassword = () => {
  if (!generatedPassword.value) {
    return;
  }

  void copyToClipboard(generatedPassword.value);
};

// --- The Priskirti sheet (O21), opened with the person fixed -----------------------------------

const assignSheetOpen = ref(false);
const sheetDuty = ref<(App.Entities.Duty & Record<string, unknown>) | null>(null);
const sheetDutiable = ref<(App.Entities.Dutiable & Record<string, unknown>) | null>(null);
const sheetUser = computed(() => props.user as unknown as App.Entities.User);

const openAssignSheet = () => {
  sheetDuty.value = null;
  sheetDutiable.value = null;
  assignSheetOpen.value = true;
};

const openTermSheet = (duty: UserDuty) => {
  sheetDuty.value = duty as unknown as App.Entities.Duty & Record<string, unknown>;
  sheetDutiable.value = (duty.pivot ?? null) as (App.Entities.Dutiable & Record<string, unknown>) | null;
  assignSheetOpen.value = true;
};

// --- Super Admin (super admin only) -----------------------------------------------------------

// The switch shows the saved state; flipping it asks first, since it grants or ends full access.
const superAdminTarget = ref<boolean | null>(null);
const { report: accessWarningReport, open: accessWarningOpen, guardedSubmit, confirm: accessWarningConfirm, cancel: accessWarningCancel } = useAccessChangeGuard();

const saveSuperAdmin = () => {
  const target = superAdminTarget.value;
  superAdminTarget.value = null;

  if (target === null) {
    return;
  }

  guardedSubmit((acknowledge) => {
    router.put(route('users.superAdmin.update', props.user.id), { is_super_admin: target, acknowledge_access_change: acknowledge }, { preserveScroll: true });
  });
};

// --- Tasks ------------------------------------------------------------------------------------

const {
  showTaskDetail,
  selectedDetailTask,
  openTaskDetail,
  closeTaskDetail,
  reportFromDetail,
} = useTaskActionDialogs();

</script>
