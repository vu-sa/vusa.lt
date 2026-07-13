<template>
  <AdminContentPage>
    <InertiaHead :title="institution.name" />

    <!-- Institution Hero -->
    <ShowPageHero
      flat
      :title="institution.name"
      :subtitle="institution.short_name"
    >
      <template #icon>
        <InstitutionIconFilled class="h-6 w-6 sm:h-7 sm:w-7 text-zinc-600 dark:text-zinc-300" />
      </template>
      <template #badge>
        <Badge v-if="primaryType" variant="secondary" class="text-xs">
          {{ primaryType }}
        </Badge>
        <Badge v-if="institution.has_public_meetings" variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
          <Globe class="h-3 w-3" />
          {{ $t('Vieši posėdžiai') }}
        </Badge>
      </template>
      <template #info>
        <div v-if="institution.managers?.length > 0" class="flex items-center gap-2">
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Vadovai') }}:</span>
          <UsersAvatarGroup :users="institution.managers" :max="3" :size="24" />
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
    </ShowPageHero>

    <!-- Main Content -->
    <Tabs v-model="currentTab" class="mt-6">
      <TabsList class="mb-4">
        <TabsTrigger value="overview">
          {{ $t('Apžvalga') }}
        </TabsTrigger>
        <TabsTrigger value="duties">
          {{ $t('Pareigos') }}
          <span v-if="institution.duties?.length" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ institution.duties.length }}
          </span>
        </TabsTrigger>
        <TabsTrigger value="meetings">
          {{ $t('Susitikimai') }}
          <span v-if="institution.meetings?.length" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ institution.meetings.length }}
          </span>
        </TabsTrigger>
        <TabsTrigger value="files">
          {{ $t('Failai') }}
        </TabsTrigger>
        <TabsTrigger value="tasks">
          {{ $t('Užduotys') }}
          <span v-if="institution.allTasks?.length" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ institution.allTasks.length }}
          </span>
        </TabsTrigger>
        <TabsTrigger value="related" :disabled="relatedInstitutionCount === 0">
          {{ $t('Susijusios institucijos') }}
        </TabsTrigger>
        <TabsTrigger value="discussion">
          {{ $t('Diskusija') }}
          <span v-if="institution.comments_count" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ institution.comments_count }}
          </span>
        </TabsTrigger>
      </TabsList>

      <TabsContent value="overview" class="space-y-6">
        <InstitutionOverviewSection
          :institution
          :can-edit-members="canManageMembers"
          @navigate-tab="navigateToTab"
          @schedule-meeting="showMeetingModal = true"
          @add-member="showAddMemberModal = true"
          @view-profile="handleViewProfile"
          @edit-member="handleEditMember"
          @view-meeting="(meeting) => router.visit(route('meetings.show', meeting.id))"
        />
      </TabsContent>

      <!-- Duties Tab -->
      <TabsContent value="duties" class="space-y-6">
        <div class="space-y-4">
          <div v-if="institution.duties?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <DutySummaryCard
              v-for="duty in sortedDuties"
              :key="duty.id"
              :duty="duty"
              :show-institution="false"
            />
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
            <div
              class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
              <UserCheck class="h-8 w-8 text-zinc-400" />
            </div>
            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
              {{ $t('Nėra pareigų') }}
            </h3>
            <p class="text-zinc-500 dark:text-zinc-400 mb-4">
              {{ $t('Šiai institucijai dar nėra priskirta pareigų.') }}
            </p>
          </div>
        </div>
      </TabsContent>

      <!-- Meetings Tab -->
      <TabsContent value="meetings" class="space-y-6">
        <Card v-if="institution.meetings?.length > 0">
          <CardContent class="p-0">
            <button
              v-for="meeting in sortedMeetings"
              :key="meeting.id"
              type="button"
              :class="[
                'flex w-full items-center gap-4 px-4 py-3 text-left',
                'border-b border-border last:border-b-0',
                'transition-colors hover:bg-accent/50',
                'focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary/50',
              ]"
              @click="router.visit(route('meetings.show', meeting.id))"
            >
              <!-- Date -->
              <span class="w-24 shrink-0 text-sm text-muted-foreground">
                {{ formatMeetingDate(meeting.start_time) }}
              </span>

              <!-- Title -->
              <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">
                {{ getMeetingTitle(meeting) }}
              </span>

              <!-- Agenda count + Vote indicators -->
              <div class="flex shrink-0 items-center gap-3 text-xs text-muted-foreground">
                <span v-if="meeting.agenda_items_count" class="whitespace-nowrap">
                  {{ meeting.agenda_items_count }} {{ meeting.agenda_items_count === 1 ? $t('klausimas') : $t('klausimai') }}
                </span>
                <MeetingOutcomeIndicators
                  v-if="(meeting.vote_matches || 0) + (meeting.vote_mismatches || 0) + (meeting.incomplete_vote_data || 0) > 0"
                  :matches="meeting.vote_matches || 0"
                  :mismatches="meeting.vote_mismatches || 0"
                  :incomplete="meeting.incomplete_vote_data || 0"
                />
              </div>

              <!-- Status badges -->
              <div class="flex shrink-0 items-center gap-1.5">
                <Badge
                  v-if="meeting.has_protocol"
                  variant="outline"
                  class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700"
                >
                  <FileCheck class="h-3 w-3" />
                  {{ $t('Protokolas') }}
                </Badge>
                <Badge
                  v-if="meeting.has_report"
                  variant="outline"
                  class="text-xs gap-1 text-blue-600 border-blue-300 dark:text-blue-400 dark:border-blue-700"
                >
                  <ClipboardCheck class="h-3 w-3" />
                  {{ $t('Ataskaita') }}
                </Badge>
              </div>

              <!-- Delete + Arrow -->
              <div class="flex shrink-0 items-center gap-1">
                <Button
                  v-if="canDeleteMeetings"
                  variant="ghost"
                  size="sm"
                  class="h-7 w-7 p-0"
                  @click.stop="handleDeleteMeeting(meeting)"
                >
                  <Trash2 class="h-3.5 w-3.5 text-muted-foreground" />
                </Button>
                <ChevronRight class="h-4 w-4 text-muted-foreground" />
              </div>
            </button>
          </CardContent>
        </Card>
        <div v-else class="py-12 text-center">
          <div
            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <CalendarIcon class="h-8 w-8 text-zinc-400" />
          </div>
          <h3 class="mb-2 text-lg font-medium text-zinc-900 dark:text-zinc-100">
            {{ $t('Nėra susitikimų') }}
          </h3>
          <p class="mb-4 text-zinc-500 dark:text-zinc-400">
            {{ $t('Šiai institucijai dar nėra suplanuota susitikimų.') }}
          </p>
          <Button class="gap-2" @click="showMeetingModal = true">
            <CalendarIcon class="h-4 w-4" />
            {{ $t('Suplanuoti susitikimą') }}
          </Button>
        </div>
      </TabsContent>

      <!-- Files Tab -->
      <TabsContent value="files" class="space-y-6">
        <Suspense v-if="institution.types.length > 0">
          <SimpleFileViewer :fileable="{ id: institution.id, type: 'Institution' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              {{ $t('Kraunami susiję failai...') }}
            </div>
          </template>
        </Suspense>
        <FileManager :starting-path="institution.sharepointPath" :fileable="{ id: institution.id, type: 'Institution' }" />
      </TabsContent>

      <!-- Tasks Tab -->
      <TabsContent value="tasks" class="space-y-6">
        <TaskManager
          :tasks="institution.allTasks"
          :taskable="{ id: institution.id, type: 'App\\Models\\Institution' }"
        />
      </TabsContent>

      <!-- Related Institutions Tab -->
      <TabsContent value="discussion" class="space-y-6">
        <DiscussionPanel commentable-type="institution" :commentable-id="institution.id" />
      </TabsContent>

      <TabsContent value="related" class="space-y-6">
        <RelatedInstitutions :institution />
      </TabsContent>
    </Tabs>

    <!-- Modals -->
    <NewMeetingDialog v-if="showMeetingModal" :show-modal="showMeetingModal" :institution
      @close="showMeetingModal = false" />

    <AddCheckInDialog v-if="showCheckInModal" :open="showCheckInModal" :institution-id="institution.id"
      @close="showCheckInModal = false" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { computed, defineAsyncComponent, ref } from 'vue';
import { router, Head as InertiaHead, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

// Icons
import {
  Calendar as CalendarIcon,
  ChevronRight,
  UserCheck,
  Globe,
  Clock,
  Eye,
  EyeOff,
  Bell,
  BellOff,
  Loader2,
  FileCheck,
  ClipboardCheck,
  Trash2,
} from 'lucide-vue-next';

// Layout and Components
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import SimpleFileViewer from '@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue';
import NewMeetingDialog from '@/Components/Dialogs/NewMeetingDialog.vue';
import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import MeetingOutcomeIndicators from '@/Components/Public/Search/MeetingOutcomeIndicators.vue';
import InstitutionOverviewSection from '@/Components/Institutions/InstitutionOverviewSection.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { DutySummaryCard } from '@/Components/Duties';

// UI Components
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

// Utils
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useInstitutionSubscription } from '@/Pages/Admin/Dashboard/Composables/useInstitutionSubscription';
import { useShowPageData } from '@/Composables/useShowPageData';
import { InstitutionIconFilled } from '@/Components/icons';

const props = defineProps<{
  institution: App.Entities.Institution;
  subscription?: {
    is_followed: boolean;
    is_muted: boolean;
    is_duty_based: boolean;
  } | null;
}>();

// State - use shared composable for tab persistence and deferred rendering
const { currentTab, navigateToTab } = useShowPageData({
  tabKey: 'institution',
  entityId: props.institution.id,
  defaultTab: 'overview',
});

const showMeetingModal = ref(false);
const showCheckInModal = ref(false);
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
const page = usePage();
const canScheduleMeeting = computed(() => {
  return page.props.auth?.can?.['meetings.create.padalinys'] || false;
});

const canAddCheckIn = computed(() => {
  return page.props.auth?.can?.['institution_check_ins.create.padalinys'] || false;
});

const canManageMembers = computed(() => {
  return page.props.auth?.can?.['institutions.update.padalinys'] || false;
});

const canDeleteMeetings = computed(() => {
  return page.props.auth?.can?.['meetings.delete.padalinys'] || false;
});

// Event handlers
const handleViewProfile = (member: App.Entities.User) => {
  router.visit(route('users.show', member.id));
};

const handleEditMember = (member: App.Entities.User) => {
  router.visit(route('users.edit', member.id));
};

const handleDeleteMeeting = (meeting: App.Entities.Meeting) => {
  if (confirm($t('Ar tikrai norite ištrinti šį susitikimą?'))) {
    router.delete(route('meetings.destroy', meeting.id));
  }
};

const sortedDuties = computed(() => {
  if (!props.institution.duties) return [];
  return [...props.institution.duties].sort((a, b) => (a.order || 0) - (b.order || 0));
});

const sortedMeetings = computed(() => {
  if (!props.institution.meetings) return [];
  return [...props.institution.meetings].sort(
    (a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime(),
  );
});

const formatMeetingDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};

const getMeetingTitle = (meeting: App.Entities.Meeting) => {
  if (meeting.title && meeting.title.trim() !== '') {
    return meeting.title;
  }
  const institutionName = props.institution.name || 'Institucijos';
  return `${institutionName} ${$t('posėdis')}`;
};

</script>
