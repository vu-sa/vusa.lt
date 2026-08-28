<template>
  <ShowPageLayout
    v-model:tab="currentTab"
    :title="institution.name"
    :subtitle="institution.short_name ?? undefined"
    :model="institution"
    audit-subject-type="institution"
    :tabs
  >
    <template #icon>
      <InstitutionIconFilled class="h-6 w-6 sm:h-7 sm:w-7 text-zinc-600 dark:text-zinc-300" />
    </template>

    <template #badge>
      <Badge v-if="primaryType" variant="secondary" class="text-xs">
        {{ primaryType }}
      </Badge>
      <InstitutionScopeBadge v-if="institution.governance_scope" :scope="institution.governance_scope" class="text-xs" />
      <Badge v-if="institution.has_public_meetings" variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
        <Globe class="h-3 w-3" />
        {{ $t('Vieši posėdžiai') }}
      </Badge>
    </template>

    <template #info>
      <div v-if="institution.managers?.length > 0" class="flex items-center gap-2">
        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Vadovai') }}:</span>
        <UsersAvatarGroup :users="institution.managers ?? []" :max="3" :size="24" />
      </div>
    </template>

    <template #actions>
      <Button v-if="canScheduleMeeting" variant="default" size="sm" class="gap-2" @click="showMeetingModal = true">
        <CalendarIcon class="h-4 w-4" />
        {{ $t('Suplanuoti susitikimą') }}
      </Button>
      <Button v-if="canAddCheckIn" variant="outline" size="sm" class="gap-2" @click="showCheckInModal = true">
        <Clock class="h-4 w-4" />
        {{ $t('Pridėti pažymą') }}
      </Button>
      <Button variant="outline" size="sm" class="gap-2" as="a"
        :href="route('dutiables.timeline', { institution: institution.id })">
        <CalendarRange class="h-4 w-4" />
        {{ $t('dutiables.timeline.open') }}
      </Button>

      <!-- Subscription buttons -->
      <TooltipProvider v-if="subscription">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="outline"
              size="sm"
              class="gap-2"
              :disabled="isDutyBased || isFollowLoading"
              @click="toggleFollow"
            >
              <Loader2 v-if="isFollowLoading" class="h-4 w-4 animate-spin" />
              <Eye v-else-if="!isFollowed" class="h-4 w-4" />
              <EyeOff v-else class="h-4 w-4" />
              {{ isFollowed ? $t('Nebesekti') : $t('Sekti') }}
            </Button>
          </TooltipTrigger>
          <TooltipContent v-if="isDutyBased">
            {{ $t('Negalima nustoti sekti institucijos, kurioje turite pareigų') }}
          </TooltipContent>
        </Tooltip>

        <Tooltip v-if="isFollowed">
          <TooltipTrigger as-child>
            <Button
              variant="outline"
              size="sm"
              class="gap-2"
              :disabled="isDutyBased || isMuteLoading"
              @click="toggleMute"
            >
              <Loader2 v-if="isMuteLoading" class="h-4 w-4 animate-spin" />
              <BellOff v-else-if="isMuted" class="h-4 w-4" />
              <Bell v-else class="h-4 w-4" />
              {{ isMuted ? $t('Įjungti pranešimus') : $t('Nutildyti') }}
            </Button>
          </TooltipTrigger>
          <TooltipContent v-if="isDutyBased">
            {{ $t('Negalima nutildyti institucijos, kurioje turite pareigų') }}
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <MoreOptionsButton edit @edit-click="router.visit(route('institutions.edit', institution.id))" />
    </template>

    <template #overview>
      <InstitutionOverviewSection
        :institution
        :can-edit-members="canManageMembers"
        @navigate-tab="navigateToTab"
        @schedule-meeting="showMeetingModal = true"
        @report-activity="showCheckInModal = true"
        @add-member="showAddMemberModal = true"
        @view-profile="handleViewProfile"
        @edit-member="handleEditMember"
        @view-meeting="(meeting) => router.visit(route('meetings.show', meeting.id))"
      />
    </template>

    <template #duties>
      <div v-if="institution.duties?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <DutySummaryCard
          v-for="duty in sortedDuties"
          :key="duty.id"
          :duty
          :show-institution="false"
        />
      </div>

      <EmptyState
        v-else
        :title="$t('Nėra pareigų')"
        :description="$t('Šiai institucijai dar nėra priskirta pareigų.')"
      >
        <template #icon>
          <UserCheck class="h-10 w-10 text-muted-foreground" />
        </template>
      </EmptyState>
    </template>

    <template #meetings>
      <InstitutionMeetingsList
        v-if="institution.meetings?.length > 0"
        :meetings="institution.meetings"
        :institution-name="institution.name"
        :can-delete="canDeleteMeetings"
        @select="(meeting) => router.visit(route('meetings.show', meeting.id))"
        @delete="handleDeleteMeeting"
      />

      <EmptyState
        v-else
        :title="$t('Nėra susitikimų')"
        :description="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
      >
        <template #icon>
          <CalendarIcon class="h-10 w-10 text-muted-foreground" />
        </template>
        <Button class="gap-2" @click="showMeetingModal = true">
          <CalendarIcon class="h-4 w-4" />
          {{ $t('Suplanuoti susitikimą') }}
        </Button>
      </EmptyState>
    </template>

    <template #files>
      <div class="space-y-6">
        <Suspense v-if="institution.types.length > 0">
          <SimpleFileViewer :fileable="{ id: institution.id, type: 'Institution' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              {{ $t('Kraunami susiję failai...') }}
            </div>
          </template>
        </Suspense>
        <FileManager :starting-path="institution.sharepointPath ?? undefined" :fileable="{ id: institution.id, type: 'Institution' }" />
      </div>
    </template>

    <template #tasks>
      <TaskManager
        :tasks="taskManagerTasks"
        :taskable="{ id: institution.id, type: ModelEnum.INSTITUTION }"
      />
    </template>

    <template #related>
      <RelatedInstitutions :institution="legacyInstitution" />
    </template>

    <template #discussion>
      <DiscussionPanel commentable-type="institution" :commentable-id="institution.id" />
    </template>

    <!-- Modals -->
    <NewMeetingDialog v-if="showMeetingModal" :show-modal="showMeetingModal" :institution="legacyInstitution"
      @close="showMeetingModal = false" />

    <AddCheckInDialog v-if="showCheckInModal" :open="showCheckInModal" :institution-id="institution.id"
      @close="showCheckInModal = false" />
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { ModelEnum } from '@/Types/enums';
import { computed, defineAsyncComponent, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Calendar as CalendarIcon,
  CalendarRange,
  UserCheck,
  Globe,
  Clock,
  Eye,
  EyeOff,
  Bell,
  BellOff,
  Loader2,
} from 'lucide-vue-next';

import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import { EmptyState } from '@/Components/Patterns';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import SimpleFileViewer from '@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue';
import NewMeetingDialog from '@/Components/Dialogs/NewMeetingDialog.vue';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import InstitutionMeetingsList from '@/Components/Institutions/InstitutionMeetingsList.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import InstitutionScopeBadge from '@/Components/Institutions/InstitutionScopeBadge.vue';
import InstitutionOverviewSection from '@/Components/Institutions/InstitutionOverviewSection.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { DutySummaryCard } from '@/Components/Duties';
import type { InstitutionPageData, InstitutionPageMeeting } from '@/Types/InstitutionPage';
// UI Components
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
// Utils
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useInstitutionSubscription } from '@/Pages/Admin/Dashboard/Composables/useInstitutionSubscription';
import { useShowPageData } from '@/Composables/useShowPageData';
import { InstitutionIconFilled } from '@/Components/icons';

const page = usePage();
const permissions = computed(
  () => page.props.auth?.can as Record<string, boolean> | undefined,
);

const props = defineProps<{
  institution: InstitutionPageData;
  subscription?: {
    is_followed: boolean;
    is_muted: boolean;
    is_duty_based: boolean;
  } | null;
}>();

const legacyInstitution = computed(
  () => props.institution as unknown as App.Entities.Institution,
);
const taskManagerTasks = computed(
  () => props.institution.allTasks as unknown as InstanceType<typeof TaskManager>['$props']['tasks'],
);

// State - use shared composable for tab persistence and deferred rendering
const { currentTab, navigateToTab } = useShowPageData({
  tabKey: 'institution',
  entityId: props.institution.id,
  defaultTab: 'overview',
});

const activityAction = new URLSearchParams(page.url.split('?')[1] ?? '').get('activityAction');
const showMeetingModal = ref(activityAction === 'register-meeting');
const showCheckInModal = ref(activityAction === 'report-activity');
const showAddMemberModal = ref(false);

// Subscription state
const isFollowed = ref(props.subscription?.is_followed ?? false);
const isMuted = ref(props.subscription?.is_muted ?? false);
const isDutyBased = computed(() => props.subscription?.is_duty_based ?? false);

const subscriptionState = computed(() => ({
  is_followed: isFollowed.value,
  is_muted: isMuted.value,
  is_duty_based: isDutyBased.value,
}));

// Use the subscription composable
const { toggleFollow: doToggleFollow, toggleMute: doToggleMute, followLoading, muteLoading } = useInstitutionSubscription();

const isFollowLoading = computed(() => followLoading.value[String(props.institution.id)] ?? false);
const isMuteLoading = computed(() => muteLoading.value[String(props.institution.id)] ?? false);

const toggleFollow = async () => {
  if (isDutyBased.value) return;

  const newState = await doToggleFollow(String(props.institution.id), subscriptionState.value, ['subscription']);
  isFollowed.value = newState;
  // If unfollowed, also unmute locally
  if (!newState) {
    isMuted.value = false;
  }
};

const toggleMute = async () => {
  if (isDutyBased.value) return;

  const newState = await doToggleMute(String(props.institution.id), subscriptionState.value, ['subscription']);
  isMuted.value = newState;
};

// Async Components
const FileManager = defineAsyncComponent(
  () => import('@/Features/Admin/SharepointFileManager/SharepointFileManager.vue'),
);

const RelatedInstitutions = defineAsyncComponent(
  () => import('@/Components/Carousels/RelatedInstitutions.vue'),
);

// Generate breadcrumbs
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

// Computed properties
const relatedInstitutionCount = computed(() => {
  // Use the flat format which includes all relationship types (direct, type-based, sibling)
  if (props.institution.relatedInstitutionsFlat?.length) {
    return props.institution.relatedInstitutionsFlat.length;
  }
  // Fallback to legacy format
  return Object.values(props.institution.relatedInstitutions || {}).reduce(
    (acc, val) => acc + (val?.length || 0),
    0,
  );
});

// Note: totalPositions, lastMeeting, daysSinceLastMeeting, isOverdue, and periodicityStatusColor
// are now calculated in useInstitutionUrgency composable and InstitutionOverviewSection

const primaryType = computed(() => {
  const type = props.institution.types?.[0];
  return typeof type?.title === 'string' ? type.title : null;
});

// Permissions
const canScheduleMeeting = computed(() => {
  return permissions.value?.['meetings.create.padalinys'] ?? false;
});

const canAddCheckIn = computed(() => {
  return permissions.value?.['institution_check_ins.create.padalinys'] ?? false;
});

const canManageMembers = computed(() => {
  return permissions.value?.['institutions.update.padalinys'] ?? false;
});

const canDeleteMeetings = computed(() => {
  return permissions.value?.['meetings.delete.padalinys'] ?? false;
});

// Event handlers
const handleViewProfile = (member: App.Entities.User) => {
  router.visit(route('users.show', member.id));
};

const handleEditMember = (member: App.Entities.User) => {
  router.visit(route('users.edit', member.id));
};

const handleDeleteMeeting = (meeting: InstitutionPageMeeting) => {
  if (confirm($t('Ar tikrai norite ištrinti šį susitikimą?'))) {
    router.delete(route('meetings.destroy', meeting.id));
  }
};

const sortedDuties = computed(() => {
  if (!props.institution.duties) return [];
  return [...props.institution.duties].sort((a, b) => (a.order || 0) - (b.order || 0));
});

/**
 * The related tab is dropped rather than disabled when there is nothing to show —
 * a tab that can't be opened is worse than one that isn't offered.
 */
const tabs = computed(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'duties', label: $t('Pareigos'), count: props.institution.duties?.length },
  { value: 'meetings', label: $t('Susitikimai'), count: props.institution.meetings?.length },
  { value: 'files', label: $t('Failai') },
  { value: 'tasks', label: $t('Užduotys'), count: props.institution.allTasks?.length },
  ...(relatedInstitutionCount.value > 0
    ? [{ value: 'related', label: $t('Susijusios institucijos'), count: relatedInstitutionCount.value }]
    : []),
  { value: 'discussion', label: $t('Diskusija'), count: props.institution.comments_count },
]);

</script>
