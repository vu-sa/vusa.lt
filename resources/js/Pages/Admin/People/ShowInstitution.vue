<template>
  <AdminContentPage>
    <InertiaHead :title="institution.name" />

    <!-- Institution Hero -->
    <ShowPageHero
      :title="institution.name"
      :subtitle="institution.short_name"
    >
      <template #icon>
        <span class="text-lg font-medium text-zinc-600 dark:text-zinc-300">
          {{ getInitials(institution.name) }}
        </span>
      </template>
      <template #badge>
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
      <TabsList class="gap-2">
        <TabsTrigger value="overview">
          {{ $t('Apžvalga') }}
        </TabsTrigger>
        <TabsTrigger value="duties">
          {{ $t('Pareigos') }} ({{ institution.duties?.length || 0 }})
        </TabsTrigger>
        <TabsTrigger value="meetings">
          {{ $t('Susitikimai') }} ({{ institution.meetings?.length || 0 }})
        </TabsTrigger>
        <TabsTrigger value="files">
          {{ $t('Failai') }}
        </TabsTrigger>
        <TabsTrigger value="related" :disabled="relatedInstitutionCount === 0">
          {{ $t('Susijusios institucijos') }}
        </TabsTrigger>
      </TabsList>

      <TabsContent value="overview" class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Active Members -->
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <Users class="h-4 w-4 text-blue-500" />
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                  {{ $t('Aktyvūs nariai') }}
                </span>
              </div>
              <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ institution.current_users?.length || 0 }}
              </div>
              <div class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $t('iš') }} {{ totalPositions }} {{ $t('pozicijų') }}
              </div>
            </CardContent>
          </Card>

          <!-- Last Meeting -->
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <CalendarIcon class="h-4 w-4 text-green-500" />
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                  {{ $t('Paskutinis susitikimas') }}
                </span>
              </div>
              <div v-if="lastMeeting" class="text-sm text-zinc-900 dark:text-zinc-100">
                {{ formatRelativeTime(lastMeeting.start_time) }}
              </div>
              <div v-else class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $t('Nėra duomenų') }}
              </div>
            </CardContent>
          </Card>

          <!-- Total Meetings -->
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <BarChart3 class="h-4 w-4 text-purple-500" />
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                  {{ $t('Susitikimų') }}
                </span>
              </div>
              <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ institution.meetings?.length || 0 }}
              </div>
              <div class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $t('šiais metais') }}
              </div>
            </CardContent>
          </Card>

          <!-- Meeting Periodicity Status -->
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <Repeat class="h-4 w-4" :class="periodicityStatusColor" />
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                  {{ $t('Periodiškumas') }}
                </span>
              </div>
              <div class="text-sm" :class="periodicityStatusColor">
                {{ $t('Kas') }} {{ institution.meeting_periodicity_days ?? 30 }} {{ $t('d.') }}
              </div>
              <div v-if="daysSinceLastMeeting !== null" class="text-xs" :class="isOverdue ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400'">
                {{ daysSinceLastMeeting }} {{ $t('d. nuo paskutinio') }}
                <span v-if="isOverdue" class="font-medium">({{ $t('vėluoja') }})</span>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="xl:col-span-2 space-y-6">
            <!-- Current Members -->
            <MemberGrid :title="$t('Dabartiniai nariai')"
              :subtitle="`${institution.current_users?.length || 0} ${$t('aktyvūs nariai')}`"
              :members="institution.current_users || []" :institution :max-positions="totalPositions"
              :show-contact="true" :show-actions="true" :can-edit="canManageMembers" :can-add-member="canManageMembers"
              @add-member="showAddMemberModal = true" @view-profile="handleViewProfile"
              @edit-member="handleEditMember" />

            <!-- Recent Activity -->
            <Card v-if="recentActivity.length > 0">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Activity class="h-5 w-5" />
                  {{ $t('Paskutinė veikla') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="space-y-3">
                  <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center gap-3 text-sm">
                    <div class="w-2 h-2 rounded-full bg-blue-500" />
                    <span class="text-zinc-600 dark:text-zinc-400">{{ activity.description }}</span>
                    <span class="text-zinc-400 dark:text-zinc-500 ml-auto">{{ formatRelativeTime(activity.created_at) }}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="xl:sticky xl:top-6 xl:self-start space-y-6">
            <!-- Quick Actions -->
            <Card>
              <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-base">
                  <Zap class="h-5 w-5 text-primary" />
                  {{ $t('Greiti veiksmai') }}
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <Button v-if="canScheduleMeeting" variant="outline" size="sm" class="w-full justify-start" @click="showMeetingModal = true">
                  <CalendarIcon class="h-4 w-4 mr-2" />
                  {{ $t('Suplanuoti susitikimą') }}
                </Button>
                <Button variant="outline" size="sm" class="w-full justify-start" @click="currentTab = 'duties'">
                  <UserCheck class="h-4 w-4 mr-2" />
                  {{ $t('Peržiūrėti pareigas') }}
                </Button>
                <Button variant="outline" size="sm" class="w-full justify-start" @click="currentTab = 'meetings'">
                  <CalendarIcon class="h-4 w-4 mr-2" />
                  {{ $t('Peržiūrėti susitikimus') }}
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </TabsContent>

      <!-- Duties Tab -->
      <TabsContent value="duties" class="space-y-6">
        <div class="space-y-4">
          <div v-if="institution.duties?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card v-for="duty in sortedDuties" :key="duty.id" class="hover:shadow-md transition-shadow cursor-pointer"
              @click="router.visit(route('duties.show', duty.id))">
              <CardContent class="p-4">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <h3 class="font-bold text-zinc-900 dark:text-zinc-100 mb-2">
                      {{ duty.name }}
                    </h3>

                    <!-- Current Members -->
                    <div class="flex items-center gap-3 mb-2">
                        <UsersAvatarGroup :users="duty.current_users" :max="3" :size="24" />

                      <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ duty.current_users?.length || 0 }} / {{ duty.places_to_occupy || 0 }} {{ $t('užimta') }}
                      </span>
                    </div>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-2">
                      <Badge :variant="getDutyStatusVariant(duty)" class="text-xs">
                        {{ getDutyStatusText(duty) }}
                      </Badge>
                      <span v-if="duty.email" class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ duty.email }}
                      </span>
                    </div>
                  </div>

                  <ChevronRight class="h-4 w-4 text-zinc-400" />
                </div>
              </CardContent>
            </Card>
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
        <div v-if="institution.meetings?.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <ModernMeetingCard v-for="meeting in institution.meetings" :key="meeting.id" :meeting :institution
            :can-delete="canDeleteMeetings" @click="router.visit(route('meetings.show', meeting.id))"
            @delete="handleDeleteMeeting(meeting)" />
        </div>
        <div v-else class="text-center py-12">
          <div
            class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
            <CalendarIcon class="h-8 w-8 text-zinc-400" />
          </div>
          <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
            {{ $t('Nėra susitikimų') }}
          </h3>
          <p class="text-zinc-500 dark:text-zinc-400 mb-4">
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

      <!-- Related Institutions Tab -->
      <TabsContent value="related" class="space-y-6">
        <RelatedInstitutions :institution />
      </TabsContent>
    </Tabs>

    <!-- Modals -->
    <NewMeetingModal v-if="showMeetingModal" :show-modal="showMeetingModal" :institution
      @close="showMeetingModal = false" />

    <AddCheckInDialog v-if="showCheckInModal" :open="showCheckInModal" :institution-id="institution.id"
      @close="showCheckInModal = false" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { computed, defineAsyncComponent, ref } from "vue";
import { router, Head as InertiaHead, usePage } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";

// Icons
import {
  Activity,
  Calendar as CalendarIcon,
  Users,
  ChevronRight,
  UserCheck,
  Globe,
  Clock,
  BarChart3,
  Repeat,
  Zap,
  Eye,
  EyeOff,
  Bell,
  BellOff,
  Loader2
} from 'lucide-vue-next';

// Layout and Components
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ShowPageHero from "@/Components/Hero/ShowPageHero.vue";
import MemberGrid from "@/Components/Members/MemberGrid.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ModernMeetingCard from "@/Components/Meetings/ModernMeetingCard.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";
import NewMeetingModal from "@/Components/Modals/NewMeetingModal.vue";
import AddCheckInDialog from "@/Components/Institutions/AddCheckInDialog.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

// UI Components
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";

// Utils
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import { useInstitutionSubscription } from "@/Pages/Admin/Dashboard/Composables/useInstitutionSubscription";

const props = defineProps<{
  institution: App.Entities.Institution;
  subscription?: {
    is_followed: boolean;
    is_muted: boolean;
    is_duty_based: boolean;
  } | null;
}>();

// State
const currentTab = useStorage("show-institution-tab", "overview");
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
  
  const newState = await doToggleFollow(String(props.institution.id), subscriptionState.value);
  isFollowed.value = newState;
  // If unfollowed, also unmute locally
  if (!newState) {
    isMuted.value = false;
  }
};

const toggleMute = async () => {
  if (isDutyBased.value) return;
  
  const newState = await doToggleMute(String(props.institution.id), subscriptionState.value);
  isMuted.value = newState;
};

// Async Components
const FileManager = defineAsyncComponent(
  () => import("@/Features/Admin/SharepointFileManager/SharepointFileManager.vue")
);

const RelatedInstitutions = defineAsyncComponent(
  () => import("@/Components/Carousels/RelatedInstitutions.vue")
);

// Generate breadcrumbs
usePageBreadcrumbs(
  BreadcrumbHelpers.adminShow(
    "Institucijos",
    "institutions.index",
    {},
    props.institution.name,
    Icons.INSTITUTION,
    Icons.INSTITUTION
  )
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
    0
  );
});

const totalPositions = computed(() => {
  return props.institution.duties?.reduce((sum, duty) => {
    return sum + (duty.places_to_occupy || 0);
  }, 0) || 0;
});

// Stats computed values
const lastMeeting = computed(() => {
  const meetings = props.institution.meetings;
  if (!meetings?.length) return null;
  return [...meetings]
    .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())[0];
});

const daysSinceLastMeeting = computed(() => {
  if (!lastMeeting.value) return null;
  const date = new Date(lastMeeting.value.start_time);
  const now = new Date();
  return Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));
});

const isOverdue = computed(() => {
  if (daysSinceLastMeeting.value === null) return false;
  const periodicity = props.institution.meeting_periodicity_days ?? 30;
  return daysSinceLastMeeting.value > periodicity;
});

const periodicityStatusColor = computed(() => {
  if (daysSinceLastMeeting.value === null) return 'text-zinc-500';
  const periodicity = props.institution.meeting_periodicity_days ?? 30;
  const ratio = daysSinceLastMeeting.value / periodicity;
  if (ratio > 1) return 'text-red-500 dark:text-red-400';
  if (ratio > 0.8) return 'text-amber-500 dark:text-amber-400';
  return 'text-green-500 dark:text-green-400';
});

const getInitials = (name?: string) => {
  if (!name) return 'IN';
  const words = name.split(' ').filter(word => word.length > 0);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  if (words.length === 1) {
    return words[0].substring(0, 2).toUpperCase();
  }
  return 'IN';
};

const activeCheckIn = computed(() => {
  // This would come from the backend - placeholder for now
  return null;
});

const recentActivity = computed(() => {
  // This would come from the backend - placeholder for now
  return [];
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

const getDutyStatusVariant = (duty: App.Entities.Duty) => {
  const currentCount = duty.current_users?.length || 0;
  const maxCount = duty.places_to_occupy || 0;

  if (currentCount === 0) return 'outline';
  if (currentCount < maxCount) return 'secondary';
  if (currentCount === maxCount) return 'default';
  return 'destructive'; // Exceeds limit
};

const getDutyStatusText = (duty: App.Entities.Duty) => {
  const currentCount = duty.current_users?.length || 0;
  const maxCount = duty.places_to_occupy || 0;

  if (currentCount === 0) return $t('Neužimta');
  if (currentCount < maxCount) return $t('Dalinai užimta');
  if (currentCount === maxCount) return $t('Pilnai užimta');
  return $t('Viršija limitą');
};

const formatRelativeTime = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

  if (diffInDays === 0) return $t('Šiandien');
  if (diffInDays === 1) return $t('Vakar');
  if (diffInDays < 7) return `${$t('Prieš')} ${diffInDays} ${$t('dienas')}`;
  return date.toLocaleDateString();
};
</script>
