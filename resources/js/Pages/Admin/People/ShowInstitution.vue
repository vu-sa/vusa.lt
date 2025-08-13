<template>
  <AdminContentPage>
    <InertiaHead :title="institution.name" />
    
    <!-- Institution Hero -->
    <InstitutionHero 
      :institution="institution"
      :meetings="institution.meetings || []"
      :active-check-in="activeCheckIn"
      :can-schedule-meeting="canScheduleMeeting"
      :can-add-check-in="canAddCheckIn"
      @schedule-meeting="showMeetingModal = true"
      @add-check-in="showCheckInModal = true"
    >
      <template #actions>
        <MoreOptionsButton edit @edit-click="router.visit(route('institutions.edit', institution.id))" />
      </template>
    </InstitutionHero>

    <!-- Main Content -->
    <Tabs v-model="currentTab" class="space-y-6">
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
        <TabsTrigger 
          value="related" 
          :disabled="relatedInstitutionCount === 0"
        >
          {{ $t('Susijusios institucijos') }}
        </TabsTrigger>
      </TabsList>

      <TabsContent value="overview" class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="xl:col-span-2 space-y-6">
            <!-- Current Members -->
            <MemberGrid
              :title="$t('Dabartiniai nariai')"
              :subtitle="`${institution.current_users?.length || 0} ${$t('aktyvūs nariai')}`"
              :members="institution.current_users || []"
              :institution="institution"
              :max-positions="totalPositions"
              :show-contact="true"
              :show-actions="true"
              :can-edit="canManageMembers"
              :can-add-member="canManageMembers"
              @add-member="showAddMemberModal = true"
              @view-profile="handleViewProfile"
              @edit-member="handleEditMember"
            />

            <!-- Managers -->
            <Card v-if="institution.managers?.length > 0">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Users class="h-5 w-5" />
                  {{ $t('Vadovai') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="flex flex-wrap gap-3">
                  <UserPopover 
                    v-for="manager in institution.managers" 
                    :key="manager.id"
                    :user="manager"
                    :size="48"
                    show-name
                    show-contact
                    class="text-center"
                  />
                </div>
              </CardContent>
            </Card>

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
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <span class="text-zinc-600 dark:text-zinc-400">{{ activity.description }}</span>
                    <span class="text-zinc-400 dark:text-zinc-500 ml-auto">{{ formatRelativeTime(activity.created_at) }}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Quick Stats -->
            <Card>
              <CardHeader>
                <CardTitle>{{ $t('Statistika') }}</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $t('Pareigos') }}</span>
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    class="h-auto p-1 font-medium hover:bg-blue-50 hover:text-blue-600"
                    @click="currentTab = 'duties'"
                  >
                    {{ institution.duties?.length || 0 }}
                  </Button>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $t('Susitikimai šiais metais') }}</span>
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    class="h-auto p-1 font-medium hover:bg-blue-50 hover:text-blue-600"
                    @click="currentTab = 'meetings'"
                  >
                    {{ institution.meetings?.length || 0 }}
                  </Button>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $t('Vadovai') }}</span>
                  <span class="font-medium">{{ institution.managers?.length || 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $t('Failai') }}</span>
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    class="h-auto p-1 font-medium hover:bg-blue-50 hover:text-blue-600"
                    @click="currentTab = 'files'"
                  >
                    {{ institution.files?.length || 0 }}
                  </Button>
                </div>
              </CardContent>
            </Card>

            <!-- Contact Information -->
            <Card v-if="institution.email || institution.phone || institution.website">
              <CardHeader>
                <CardTitle>{{ $t('Kontaktai') }}</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <a v-if="institution.email" 
                   :href="`mailto:${institution.email}`"
                   class="flex items-center gap-2 text-sm hover:text-blue-600">
                  <Mail class="h-4 w-4" />
                  {{ institution.email }}
                </a>
                <a v-if="institution.phone" 
                   :href="`tel:${institution.phone}`"
                   class="flex items-center gap-2 text-sm hover:text-blue-600">
                  <Phone class="h-4 w-4" />
                  {{ institution.phone }}
                </a>
                <a v-if="institution.website" 
                   :href="institution.website"
                   target="_blank"
                   class="flex items-center gap-2 text-sm hover:text-blue-600">
                  <ExternalLink class="h-4 w-4" />
                  {{ $t('Tinklapis') }}
                </a>
              </CardContent>
            </Card>

            <!-- Last Meeting Quick Info -->
            <LastMeetingCard 
              v-if="institution?.lastMeeting"
              :last-meeting="institution.lastMeeting" 
              :institution="institution"
            />
          </div>
        </div>
      </TabsContent>

      <TabsContent value="duties">
        <div class="space-y-4">
          <div v-if="institution.duties?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card 
              v-for="duty in sortedDuties" 
              :key="duty.id"
              class="hover:shadow-md transition-shadow cursor-pointer"
              @click="router.visit(route('duties.show', duty.id))"
            >
              <CardContent class="p-4">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <h3 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">
                      {{ duty.name }}
                    </h3>
                    <p v-if="duty.description" class="text-sm text-zinc-600 dark:text-zinc-400 mb-3 line-clamp-2">
                      {{ duty.description }}
                    </p>
                    
                    <!-- Current Members -->
                    <div class="flex items-center gap-3 mb-2">
                      <div class="flex -space-x-2">
                        <UserPopover
                          v-for="user in duty.current_users?.slice(0, 3)"
                          :key="user.id"
                          :user="user"
                          :size="24"
                          class="border-2 border-white dark:border-zinc-900"
                        />
                        <div 
                          v-if="(duty.current_users?.length || 0) > 3"
                          class="w-6 h-6 rounded-full bg-zinc-100 dark:bg-zinc-800 border-2 border-white dark:border-zinc-900 flex items-center justify-center text-xs font-medium text-zinc-600 dark:text-zinc-400"
                        >
                          +{{ (duty.current_users?.length || 0) - 3 }}
                        </div>
                      </div>
                      
                      <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ duty.current_users?.length || 0 }} / {{ duty.places_to_occupy || 0 }} {{ $t('užimta') }}
                      </span>
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="flex items-center gap-2">
                      <Badge 
                        :variant="getDutyStatusVariant(duty)"
                        class="text-xs"
                      >
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
            <div class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
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

      <TabsContent value="meetings">
        <div v-if="institution.meetings?.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <ModernMeetingCard 
            v-for="meeting in institution.meetings" 
            :key="meeting.id" 
            :meeting="meeting"
            :institution="institution"
            :can-delete="canDeleteMeetings"
            @click="router.visit(route('meetings.show', meeting.id))"
            @delete="handleDeleteMeeting(meeting)"
          />
        </div>
        <div v-else class="text-center py-12">
          <div class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
            <Calendar class="h-8 w-8 text-zinc-400" />
          </div>
          <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
            {{ $t('Nėra susitikimų') }}
          </h3>
          <p class="text-zinc-500 dark:text-zinc-400 mb-4">
            {{ $t('Šiai institucijai dar nėra suplanuota susitikimų.') }}
          </p>
          <Button @click="showMeetingModal = true" class="gap-2">
            <Calendar class="h-4 w-4" />
            {{ $t('Suplanuoti susitikimą') }}
          </Button>
        </div>
      </TabsContent>

      <TabsContent value="files">
        <Suspense v-if="institution.types.length > 0">
          <SimpleFileViewer :fileable="{ id: institution.id, type: 'Institution' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              {{ $t('Kraunami susiję failai...') }}
            </div>
          </template>
        </Suspense>
        <FileManager :starting-path="institution.sharepointPath" :fileable="{ ...institution, type: 'Institution' }" />
      </TabsContent>

      <TabsContent value="related">
        <RelatedInstitutions :institution="institution" />
      </TabsContent>
    </Tabs>

    <!-- Modals -->
    <NewMeetingModal 
      v-if="showMeetingModal"
      :show-modal="showMeetingModal"
      :institution="institution"
      @close="showMeetingModal = false"
    />

    <AddCheckInDialog 
      v-if="showCheckInModal"
      :open="showCheckInModal"
      :institution-id="institution.id"
      @close="showCheckInModal = false"
      @created="handleCheckInCreated"
    />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { computed, defineAsyncComponent, ref } from "vue";
import { router, Head as InertiaHead, usePage } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";

// Layout and Components
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import InstitutionHero from "@/Components/Institutions/InstitutionHero.vue";
import MemberGrid from "@/Components/Members/MemberGrid.vue";
import LastMeetingCard from "@/Components/Cards/QuickContentCards/LastMeetingCard.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ModernMeetingCard from "@/Components/Meetings/ModernMeetingCard.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";
import NewMeetingModal from "@/Components/Modals/NewMeetingModal.vue";
import AddCheckInDialog from "@/Components/CheckIns/AddCheckInDialog.vue";

// UI Components
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import UserPopover from "@/Components/Avatars/UserPopover.vue";

// Icons
import { Activity, Mail, Phone, ExternalLink, Calendar, Users, ChevronRight, UserCheck } from 'lucide-vue-next';

// Utils
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

const props = defineProps<{
  institution: App.Entities.Institution;
}>();

// State
const currentTab = useStorage("show-institution-tab", "overview");
const showMeetingModal = ref(false);
const showCheckInModal = ref(false);
const showAddMemberModal = ref(false);

// Async Components
const FileManager = defineAsyncComponent(
  () => import("@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue")
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

const handleCheckInCreated = () => {
  // Refresh the page or update the check-in status
  router.reload();
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
  if (diffInDays < 7) return $t('Prieš') + ' ' + diffInDays + ' ' + $t('dienas');
  return date.toLocaleDateString();
};
</script>
