<template>
  <ShowPageLayout
    :title="user.name"
    :subtitle
    :model="user"
    audit-subject-type="user"
    :icon-frame="false"
    :tabs
    tab-storage-key="show-user-tab"
  >
    <template #icon>
      <UserAvatar :user size="xl" border class="h-12 w-12 sm:h-14 sm:w-14" />
    </template>
    <template #badge>
      <Badge v-if="pronounsBadge" variant="secondary" class="text-xs">
        {{ pronounsBadge }}
      </Badge>
    </template>
    <template #actions>
      <Button v-if="canEdit" variant="outline" size="sm" class="gap-2" @click="timelineOpen = true">
        <CalendarRange class="h-4 w-4" />
        {{ $t('dutiables.timeline.open') }}
      </Button>
      <Button v-if="canEdit" variant="default" size="sm" class="gap-2" @click="handleEdit">
        <Pencil class="h-4 w-4" />
        {{ $t('Redaguoti') }}
      </Button>
      <!-- Rendered only for someone who may actually act: an empty menu is noise. -->
      <MoreOptionsButton
        v-if="canEdit || canDelete"
        :edit="canEdit"
        :delete="canDelete"
        @edit-click="handleEdit"
        @delete-click="handleDelete"
      />
    </template>

    <!-- Overview Tab -->
    <template #overview>
      <ShowPageGrid>
        <template #main>
          <DutySectionList
            v-if="currentDuties.length"
            :title="$t('Dabartinės pareigos')"
            :icon="Briefcase"
            :duties="currentDuties"
            :holder="dutyHolder"
          />

          <DutySectionList
            v-if="previousDuties.length"
            :title="$t('Buvusios pareigos')"
            :icon="History"
            :duties="previousDuties"
            :holder="dutyHolder"
            muted
          />

          <EmptyState
            v-if="!allDuties.length"
            :title="$t('Pareigų nėra')"
            :description="$t('Nėra priskirtų pareigų')"
          >
            <template #icon>
              <Briefcase class="h-10 w-10 text-muted-foreground" />
            </template>
          </EmptyState>
        </template>

        <template #sidebar>
          <!-- Plain sections, not cards: the duty cards are the page's only card
               surface, and wrapping these in a second one flattens that contrast. -->
          <section>
            <SectionHeading :title="$t('Kontaktinė informacija')" :icon="Contact" class="mb-3" />
            <dl class="space-y-3 text-sm">
              <div v-if="user.email" class="flex items-center gap-3">
                <dt class="sr-only">{{ $t('El. paštas') }}</dt>
                <Mail class="icon-inline shrink-0 text-muted-foreground" />
                <dd class="min-w-0 truncate">
                  <a :href="`mailto:${user.email}`" class="transition hover:text-vusa-red">
                    {{ user.email }}
                  </a>
                </dd>
              </div>
              <div v-if="user.phone" class="flex items-center gap-3">
                <dt class="sr-only">{{ $t('Telefonas') }}</dt>
                <Phone class="icon-inline shrink-0 text-muted-foreground" />
                <dd class="min-w-0 truncate">
                  <a :href="`tel:${user.phone}`" class="tabular-nums transition hover:text-vusa-red">
                    {{ user.phone }}
                  </a>
                </dd>
              </div>
              <div v-if="user.facebook_url" class="flex items-center gap-3">
                <dt class="sr-only">Facebook</dt>
                <Globe class="icon-inline shrink-0 text-muted-foreground" />
                <dd class="min-w-0 truncate">
                  <a :href="user.facebook_url" target="_blank" rel="noopener" class="transition hover:text-vusa-red">
                    {{ $t('Facebook') }}
                  </a>
                </dd>
              </div>
            </dl>
            <p v-if="!hasContactDetails" class="text-sm text-muted-foreground">
              {{ $t('Kontaktų nenurodyta') }}
            </p>
          </section>

          <section v-if="roles.length">
            <SectionHeading :title="$t('Rolės')" :icon="Shield" :count="roles.length" class="mb-3" />
            <div class="flex flex-wrap gap-1.5">
              <Badge v-for="role in roles" :key="role.id" variant="secondary" class="font-normal">
                {{ role.name }}
              </Badge>
            </div>
          </section>
        </template>

      </ShowPageGrid>
    </template>

    <!-- Duties Tab -->
    <template #duties>
      <div class="space-y-6">
        <DutySectionList
          v-if="currentDuties.length"
          :title="$t('Dabartinės pareigos')"
          :icon="Briefcase"
          :duties="currentDuties"
          :holder="dutyHolder"
        />

        <DutySectionList
          v-if="previousDuties.length"
          :title="$t('Buvusios pareigos')"
          :icon="History"
          :duties="previousDuties"
          :holder="dutyHolder"
          muted
        />

        <EmptyState
          v-if="!allDuties.length"
          :title="$t('Pareigų nėra')"
          :description="$t('Nėra priskirtų pareigų')"
        >
          <template #icon>
            <Briefcase class="h-10 w-10 text-muted-foreground" />
          </template>
        </EmptyState>
      </div>
    </template>

    <!-- Tasks Tab -->
    <template #tasks>
      <TaskManager
        :tasks
        :task-stats
        :disabled="false"
        @open-meeting-modal="openMeetingModal"
        @open-check-in-dialog="openCheckInDialog"
        @open-task-detail="openTaskDetail"
      />
    </template>
    <DutiableTimelineDialog v-model:open="timelineOpen" scope-type="user" :scope-id="user.id" />

    <!-- Check-in dialog for periodicity gap tasks assigned to this user -->
    <AddCheckInDialog
      v-if="selectedCheckInTask"
      :open="showCheckInDialog"
      :institution-id="selectedCheckInTask.taskable_id"
      :institution-name="selectedCheckInTask.taskable?.name"
      :initial-start-date="checkInStartDate"
      :initial-end-date="checkInEndDate"
      @close="closeCheckInDialog"
    />

    <!-- Task detail dialog -->
    <TaskDetailDialog
      v-if="selectedDetailTask"
      :open="showTaskDetail"
      :task="selectedDetailTask"
      @close="closeTaskDetail"
      @schedule-meeting="scheduleMeetingFromDetail"
      @report-no-meeting="reportNoMeetingFromDetail"
    />
  </ShowPageLayout>
</template>

<script setup lang="ts">
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { computed, defineAsyncComponent, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Briefcase,
  CalendarRange,
  Contact,
  Globe,
  History,
  KeyRound,
  Mail,
  Pencil,
  Phone,
  Shield,
} from 'lucide-vue-next';

// Layout and Components
import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { EmptyState, SectionHeading, ShowPageGrid } from '@/Components/Patterns';
import { DutiableTimelineDialog } from '@/Features/Admin/DutiableTimeline';
import { DutySectionList } from '@/Components/Duties';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';

const AddCheckInDialog = defineAsyncComponent(() => import('@/Components/Institutions/AddCheckInDialog.vue'));
const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

// UI Components
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

// Utils
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { UserIconFilled } from '@/Components/icons';
import { changeDutyNameEndings } from '@/Utils/String';

const props = defineProps<{
  user: App.Entities.User & {
    current_duties: Array<App.Entities.Duty & { pivot?: { start_date?: string; end_date?: string | null; additional_email?: string } }>;
    previous_duties: Array<App.Entities.Duty & { pivot?: { start_date?: string; end_date?: string | null; additional_email?: string } }>;
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
  taskStats: {
    total: number;
    completed: number;
    pending: number;
    overdue: number;
    autoCompleting: number;
  };
  can?: { update: boolean; delete?: boolean };
}>();

// Computed
const timelineOpen = ref(false);

const currentDuties = computed(() => props.user.current_duties ?? []);
const previousDuties = computed(() => props.user.previous_duties ?? []);
const allDuties = computed(() => [...currentDuties.value, ...previousDuties.value]);
const roles = computed(() => props.user.roles ?? []);

const tabs = computed(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'duties', label: $t('Pareigos'), count: allDuties.value.length },
  { value: 'tasks', label: $t('Užduotys'), count: props.taskStats.total },
]);

/**
 * The person's headline role, not their contacts — those live in the sidebar
 * card, where they are labelled and actionable.
 */
const subtitle = computed(() => {
  const primaryDuty = currentDuties.value[0];
  return primaryDuty ? inflectedDutyName(primaryDuty) : undefined;
});

const hasContactDetails = computed(() =>
  Boolean(props.user.email || props.user.phone || props.user.facebook_url),
);

const pronounsBadge = computed(() => {
  if (!props.user.show_pronouns || !props.user.pronouns) return null;
  const p = props.user.pronouns;
  if (typeof p === 'string') return p;
  if (typeof p === 'object' && p !== null) {
    return getTranslatedValue(p) || null;
  }
  return null;
});

/**
 * The person whose duties are being listed — drives the duty-name ending
 * inflection (e.g. "Koordinatorius" → "Koordinatorė") so this profile's duties
 * read in their gender, matching the public contacts page.
 */
const dutyHolder = computed(() => ({
  name: props.user.name,
  pronouns: props.user.pronouns,
}));

// Used for the hero subtitle, where DutySummaryCard isn't rendered.
const inflectedDutyName = (duty: { name: string; pivot?: { use_original_duty_name?: boolean } | null }) => {
  const { locale } = usePage().props.app;
  const rawPronouns = props.user.pronouns;
  const pronouns = typeof rawPronouns === 'string'
    ? rawPronouns
    : (rawPronouns?.[locale as 'lt' | 'en'] ?? '');
  return changeDutyNameEndings(
    props.user,
    duty.name,
    locale,
    pronouns,
    duty.pivot?.use_original_duty_name ?? false,
  );
};

// Permissions
const page = usePage();
// From the controller, not `auth.can`: that map holds index/create/forceDelete only, so
// the old lookup was always undefined and these buttons rendered for nobody.
const canEdit = computed(() => props.can?.update ?? false);
const canDelete = computed(() => props.can?.delete ?? false);
const canGeneratePassword = computed(() => canEdit.value && page.props.auth?.user?.is_super_admin);

// Event handlers
const handleEdit = () => {
  router.get(route('users.edit', props.user.id));
};

const handleDelete = () => {
  router.delete(route('users.destroy', props.user.id));
};

const handleGeneratePassword = () => {
  router.post(route('users.generatePassword', props.user.id));
};

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

// Breadcrumbs
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    $t('Nariai'),
    'users.index',
    undefined,
    props.user.name,
    UserIconFilled,
    UserIconFilled,
  ),
);
</script>
