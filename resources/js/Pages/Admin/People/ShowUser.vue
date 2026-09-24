<template>
  <RecordPage
    v-model:section="currentSection"
    :title="user.name"
    :entity-type="ModelEnum.USER"
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
          {{ $t('Šis slaptažodis bus rodomas tik vieną kartą! Įsitikinkite, kad jį išsaugojote saugiai.') }}
        </p>
      </div>
    </template>

    <template #duties>
      <div class="space-y-8">
        <section v-for="group in dutyGroups" :key="group.key" :data-group="group.key">
          <div class="flex items-center justify-between gap-2 border-b border-border pb-3">
            <h3 class="text-base font-semibold text-foreground">
              {{ group.title }}
              <span class="ml-1 text-sm font-normal text-muted-foreground tabular-nums">{{ group.duties.length }}</span>
            </h3>
          </div>
          <div class="divide-y divide-border">
            <UserTermRow
              v-for="duty in group.duties"
              :key="`${duty.id}-${duty.pivot?.id}`"
              :duty
              :holder="dutyHolder"
              :can-manage="Boolean(can?.update) && group.key !== 'previous'"
              @edit="openTermSheet(duty)"
              @end="openTermSheet(duty)"
            />
          </div>
        </section>

        <EmptyState
          v-if="!allDuties.length"
          :title="$t('Pareigų nėra')"
          :description="$t('Šiam nariui dar nėra priskirta pareigybių.')"
          :icon="Briefcase"
          :action-label="can?.update ? $t('Pridėti pareigybę') : undefined"
          @action="openAssignSheet"
        />
      </div>
    </template>

    <template #roles>
      <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
          <h3 class="text-base font-semibold text-foreground">
            {{ $t('Rolės') }}
            <span class="ml-1 text-sm font-normal text-muted-foreground tabular-nums">{{ roles.length }}</span>
          </h3>
          <Button v-if="can?.updateRoles" type="button" variant="outline" size="sm" class="pointer-coarse:h-11" @click="rolesOpen = true">
            <Shield class="size-4" aria-hidden="true" />
            {{ $t('Keisti roles') }}
          </Button>
        </div>
        <ul v-if="roles.length" class="flex flex-wrap gap-2">
          <li
            v-for="role in roles"
            :key="role.id"
            class="border border-border bg-secondary px-2 py-1 text-sm"
          >
            {{ $t(role.name) }}
          </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">
          {{ $t('Roles nėra — prieiga kyla iš pareigybių.') }}
        </p>
      </div>
    </template>

    <template #tasks>
      <TaskManager
        :tasks
        :task-stats
        :disabled="false"
        @open-task-detail="openTaskDetail"
      />
    </template>

    <template #activity>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-foreground">
            {{ $t('Veikla') }}
          </h2>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ $t('Įrašo pakeitimai.') }}
          </p>
        </div>
        <ActivityLogSheet subject-type="user" :subject-id="String(user.id)" />
      </div>
    </template>
  </RecordPage>

  <AssignDutyUserSheet
    v-model:open="assignSheetOpen"
    :duty="sheetDuty"
    :dutiable="sheetDutiable"
    :user="sheetUser"
    :study-programs="assignment?.studyPrograms ?? []"
  />

  <SheetForm
    v-model:open="rolesOpen"
    :title="$t('Keisti roles')"
    :description="$t('Rolės suteikia prieigą nepriklausomai nuo pareigybių. Jas keisti gali tik superadministratorius.')"
    :processing="rolesForm.processing"
    :dirty="rolesForm.isDirty"
    @submit="saveRoles"
  >
    <MultiSelect
      v-model="selectedRoles"
      :options="roleOptions"
      label-field="label"
      value-field="value"
      :placeholder="$t('Be rolės...')"
    />
    <p v-if="rolesForm.errors.roles" class="text-xs text-destructive">
      {{ rolesForm.errors.roles }}
    </p>
  </SheetForm>

  <ConfirmDialog
    v-model:open="generateOpen"
    :title="$t('Ar tikrai norite sugeneruoti naują slaptažodį šiam naudotojui?')"
    :description="user.has_password ? $t('Dėmesio: Tai pakeis esamą naudotojo slaptažodį!') : undefined"
    :confirm-label="$t('Generuoti')"
    @confirm="generatePassword"
  />
  <ConfirmDialog
    v-model:open="deletePasswordOpen"
    :title="$t('Ar tikrai norite ištrinti šio naudotojo slaptažodį?')"
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
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Briefcase, CalendarRange, Copy, Edit3, KeyRound, Plus, Shield, Trash2 } from 'lucide-vue-next';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { ActionDescriptor } from '@/Components/Layouts/RecordPageAction.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { MultiSelect } from '@/Components/ui/multi-select';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { DutiableTimelineDialog } from '@/Features/Admin/DutiableTimeline';
import { AssignDutyUserSheet, UserTermRow, termStatus } from '@/Features/Admin/Occupancy';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { ModelEnum } from '@/Types/enums';
import { changeDutyNameEndings } from '@/Utils/String';
import { formatStaticTime } from '@/Utils/IntlTime';
import { todayIso } from '@/Utils/dateTime';

const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

interface Pivot { id?: string; start_date?: string; end_date?: string | null; additional_email?: string | null; use_original_duty_name?: boolean }
type UserDuty = App.Entities.Duty & { pivot?: Pivot };

const props = defineProps<{
  user: App.Entities.User & {
    current_duties: UserDuty[];
    previous_duties: UserDuty[];
    roles: Array<{ id: string; name: string }>;
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
  can?: { update: boolean; delete?: boolean; updateRoles?: boolean; managePasswords?: boolean };
  /** Deferred (`userPanels`): the sheets' options, sent only to someone who may act on them. */
  assignment?: { studyPrograms: (App.Entities.StudyProgram & { tenant_id?: number | null })[]; roles: Array<{ id: number; name: string }> } | null;
}>();

// --- Sections and key facts -------------------------------------------------------------------

const currentSection = ref('duties');

const allTerms = computed(() => [...(props.user.current_duties ?? []), ...(props.user.previous_duties ?? [])]);
const allDuties = allTerms;

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
    { key: 'current', title: $t('Dabartinės pareigos'), duties: groups.current },
    { key: 'upcoming', title: $t('Būsimos pareigos'), duties: groups.upcoming },
    { key: 'previous', title: $t('Buvusios pareigos'), duties: groups.previous },
  ].filter(group => group.duties.length > 0);
});

const currentDuties = computed(() => dutyGroups.value.find(group => group.key === 'current')?.duties ?? []);
const roles = computed(() => props.user.roles ?? []);
const showRolesSection = computed(() => roles.value.length > 0 || Boolean(props.can?.updateRoles));

const tabs = computed<RecordPageSection[]>(() => [
  { value: 'duties', label: $t('Pareigos'), count: allDuties.value.length },
  ...(showRolesSection.value ? [{ value: 'roles', label: $t('Rolės'), count: roles.value.length }] : []),
  // `pending`, not `total`: a finished task is not something the reader still has to act on.
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

  facts.push({ key: 'duties', label: $t('Dabartinės pareigos'), value: String(currentDuties.value.length) });

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

/**
 * The person whose duties are listed — drives the duty-name ending inflection
 * ("Koordinatorius" → "Koordinatorė"), matching the public contacts page.
 */
const dutyHolder = computed(() => {
  const { locale } = usePage().props.app;
  const raw = props.user.pronouns;
  const pronouns = typeof raw === 'string' ? raw : (raw?.[locale as 'lt' | 'en'] ?? '');

  return { name: props.user.name, pronouns };
});

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
const hasCopied = ref(false);

const copyPassword = () => {
  if (!generatedPassword.value) {
    return;
  }

  void navigator.clipboard.writeText(generatedPassword.value).then(() => {
    hasCopied.value = true;
    setTimeout(() => {
      hasCopied.value = false;
    }, 2000);
  });
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

// --- Roles (super admin) ----------------------------------------------------------------------

const rolesOpen = ref(false);
const rolesForm = useForm({ roles: (props.user.roles ?? []).map(role => Number(role.id)) });

const roleOptions = computed(() => (props.assignment?.roles ?? []).map(role => ({ label: $t(role.name), value: role.id })));

const selectedRoles = computed({
  get: () => roleOptions.value.filter(option => rolesForm.roles.includes(Number(option.value))),
  set: (items: { label: string; value: number }[]) => {
    rolesForm.roles = items.map(item => Number(item.value));
  },
});

const saveRoles = () => {
  rolesForm.put(route('users.roles.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      rolesForm.defaults();
      rolesOpen.value = false;
    },
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
