<template>
  <AdminContentPage>
    <InertiaHead :title="duty.name" />

    <!-- Duty Hero -->
    <ShowPageHero
      :title="duty.name"
      :subtitle="heroSubtitle"
    >
      <template #icon>
        <span class="text-lg font-medium text-zinc-600 dark:text-zinc-300">
          {{ getInitials(duty.name) }}
        </span>
      </template>
      <template #badge>
        <Badge v-if="duty.email" variant="outline" class="gap-1 text-xs">
          <Mail class="h-3 w-3" />
          {{ duty.email }}
        </Badge>
      </template>
      <template #actions>
        <Button v-if="canAssignMembers" variant="default" size="sm" class="gap-2" @click="showAssignMemberModal = true">
          <UserPlus class="h-4 w-4" />
          {{ $t('Priskirti narį') }}
        </Button>
        <Button v-if="canManageDuty" variant="outline" size="sm" class="gap-2" @click="handleEdit">
          <Settings class="h-4 w-4" />
          {{ $t('Valdyti') }}
        </Button>
        <MoreOptionsButton edit delete @edit-click="handleEdit" @delete-click="handleDelete" />
      </template>
    </ShowPageHero>

    <!-- Main Content with Tabs -->
    <Tabs v-model="currentTab" class="mt-6">
      <TabsList class="gap-2">
        <TabsTrigger value="overview">
          {{ $t('Apžvalga') }}
        </TabsTrigger>
        <TabsTrigger value="members">
          {{ $t('Nariai') }}
        </TabsTrigger>
        <TabsTrigger value="files">
          {{ $t('Failai') }}
        </TabsTrigger>
      </TabsList>

      <!-- Overview Tab -->
      <TabsContent value="overview" class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="xl:col-span-2 space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Occupancy Status -->
              <Card>
                <CardContent class="p-4">
                  <div class="flex items-center gap-2 mb-2">
                    <Users class="h-4 w-4 text-blue-500" />
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                      {{ $t('Užimtumas') }}
                    </span>
                  </div>
                  <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ currentMembersCount }} / {{ maxPositions }}
                  </div>
                  <div class="text-xs" :class="occupancyStatusColor">
                    {{ occupancyStatus }}
                  </div>
                </CardContent>
              </Card>

              <!-- Total Historical Members -->
              <Card>
                <CardContent class="p-4">
                  <div class="flex items-center gap-2 mb-2">
                    <History class="h-4 w-4 text-amber-500" />
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                      {{ $t('Istoriniai nariai') }}
                    </span>
                  </div>
                  <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ totalHistoricalMembers }}
                  </div>
                  <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $t('per visą laiką') }}
                  </div>
                </CardContent>
              </Card>

              <!-- Duty Status -->
              <Card>
                <CardContent class="p-4">
                  <div class="flex items-center gap-2 mb-2">
                    <BadgeCheck class="h-4 w-4 text-purple-500" />
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                      {{ $t('Statusas') }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div
                      class="w-2 h-2 rounded-full"
                      :class="isActive ? 'bg-green-500' : 'bg-red-500'"
                    />
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                      {{ isActive ? $t('Aktyvios') : $t('Neaktyvios') }}
                    </span>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Current Members Preview -->
            <Card v-if="filteredUsers.currentUsers.length > 0">
              <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-base">
                  <Users class="h-5 w-5 text-primary" />
                  {{ $t('Dabartiniai nariai') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="flex flex-wrap gap-2">
                  <div v-for="member in filteredUsers.currentUsers.slice(0, 6)" :key="member.id" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-medium text-primary">
                      {{ member.name?.charAt(0) }}
                    </div>
                    <span class="text-sm">{{ member.name }}</span>
                  </div>
                  <Button v-if="filteredUsers.currentUsers.length > 6" variant="ghost" size="sm" @click="currentTab = 'members'">
                    +{{ filteredUsers.currentUsers.length - 6 }} {{ $t('daugiau') }}
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Quick Actions -->
            <Card>
              <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-base">
                  <Zap class="h-5 w-5 text-primary" />
                  {{ $t('Greiti veiksmai') }}
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <Button v-if="canAssignMembers" variant="outline" size="sm" class="w-full justify-start" @click="showAssignMemberModal = true">
                  <UserPlus class="h-4 w-4 mr-2" />
                  {{ $t('Priskirti narį') }}
                </Button>
                <Button variant="outline" size="sm" class="w-full justify-start" @click="currentTab = 'members'">
                  <Users class="h-4 w-4 mr-2" />
                  {{ $t('Peržiūrėti narius') }}
                </Button>
                <Button v-if="canManageDuty" variant="outline" size="sm" class="w-full justify-start" @click="handleEdit">
                  <Settings class="h-4 w-4 mr-2" />
                  {{ $t('Redaguoti pareigas') }}
                </Button>
              </CardContent>
            </Card>

            <!-- Institution Link -->
            <Card v-if="duty.institution">
              <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-base">
                  <Building class="h-5 w-5 text-primary" />
                  {{ $t('Institucija') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Link
                  :href="route('institutions.show', duty.institution.id)"
                  class="flex items-center gap-3 p-3 -m-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
                >
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 group-hover:text-primary truncate">
                      {{ duty.institution.name }}
                    </p>
                  </div>
                  <ChevronRight class="h-4 w-4 text-zinc-400 group-hover:text-primary" />
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>
      </TabsContent>

      <!-- Members Tab -->
      <TabsContent value="members" class="space-y-6">
        <MemberHistory
          :members="allMembers"
          :show-contact="true"
          :can-edit="canManageDuty"
          @edit-period="handleEditDutyPeriod"
        />
      </TabsContent>

      <!-- Files Tab -->
      <TabsContent value="files" class="space-y-6">
        <!-- Direct Duty Files (with upload capability) -->
        <div v-if="duty.sharepointPath">
          <h3 class="text-lg font-medium mb-4">{{ $t('Pareigybės failai') }}</h3>
          <FileManager :starting-path="duty.sharepointPath" :fileable="{ id: duty.id, type: 'Duty' }" />
        </div>

        <!-- Type-inherited Files (read-only, from associated Types) -->
        <div v-if="hasTypeFiles">
          <h3 class="text-lg font-medium mb-4">{{ $t('Susiję failai pagal tipą') }}</h3>
          <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            {{ $t('Šie failai yra susiję su pareigybės tipais ir yra bendrinami tarp visų tos kategorijos pareigybių.') }}
          </p>
          <Suspense>
            <SimpleFileViewer :fileable="{ id: duty.id, type: 'Duty' }" />
            <template #fallback>
              <div class="flex h-24 items-center justify-center">
                {{ $t('Kraunami susiję failai...') }}
              </div>
            </template>
          </Suspense>
        </div>

        <!-- No files state -->
        <div v-if="!duty.sharepointPath && !hasTypeFiles" class="text-center py-8">
          <p class="text-zinc-500 dark:text-zinc-400">
            {{ $t('Pareigybė neturi failų ir nėra priskirta tipams su failais.') }}
          </p>
        </div>
      </TabsContent>
    </Tabs>

    <!-- Modals -->
    <Dialog v-model:open="showAssignMemberModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t('Priskirti narį pareigoms') }}</DialogTitle>
        </DialogHeader>
        <div class="py-4">
          <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $t('Narių priskyrimo funkcionalumas bus implementuotas ateityje.') }}
          </p>
        </div>
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";
import { router, Head as InertiaHead, usePage, Link } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";
import { Users, History, BadgeCheck, UserPlus, Settings, Zap, Building, Mail, ChevronRight } from 'lucide-vue-next';

// Layout and Components
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ShowPageHero from "@/Components/Hero/ShowPageHero.vue";
import MemberHistory from "@/Components/Members/MemberHistory.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/SharepointFileManager.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";

// UI Components
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";

// Utils
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

const props = defineProps<{
  duty: App.Entities.Duty & { sharepointPath?: string };
}>();

// State
const currentTab = useStorage("show-duty-tab", "overview");
const showAssignMemberModal = ref(false);

// Hero computed values
const heroSubtitle = computed(() => {
  return props.duty.institution?.name;
});

const getInitials = (name?: string) => {
  if (!name) return 'PA';
  const words = name.split(' ').filter(word => word.length > 0);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  if (words.length === 1) {
    return words[0].substring(0, 2).toUpperCase();
  }
  return 'PA';
};

// Computed properties
const filteredUsers = computed(() => {
  if (!props.duty.users) return { currentUsers: [], oldUsers: [] };

  return props.duty.users.reduce(
    (acc, user: App.Entities.User) => {
      if (!user.pivot) return acc;

      if (user.pivot.end_date === null) {
        acc.currentUsers.push(user);
        return acc;
      }

      if (
        new Date(user.pivot.start_date) <= new Date() &&
        new Date(user.pivot.end_date) >= new Date()
      ) {
        acc.currentUsers.push(user);
      } else {
        acc.oldUsers.push(user);
      }
      return acc;
    },
    { currentUsers: [] as App.Entities.User[], oldUsers: [] as App.Entities.User[] }
  );
});

const allMembers = computed(() => {
  return [...filteredUsers.value.currentUsers, ...filteredUsers.value.oldUsers];
});

const hasTypeFiles = computed(() => {
  return props.duty.types && props.duty.types.length > 0;
});

// Stats computed values
const maxPositions = computed(() => {
  return props.duty.places_to_occupy || 0;
});

const currentMembersCount = computed(() => {
  return filteredUsers.value.currentUsers.length;
});

const totalHistoricalMembers = computed(() => {
  return filteredUsers.value.oldUsers.length;
});

const isActive = computed(() => {
  return currentMembersCount.value > 0;
});

const occupancyStatus = computed(() => {
  const current = currentMembersCount.value;
  const max = maxPositions.value;

  if (current === 0) return $t('Neužimta');
  if (current < max) return $t('Dalinai užimta');
  if (current === max) return $t('Pilnai užimta');
  return $t('Viršija limitą');
});

const occupancyStatusColor = computed(() => {
  const current = currentMembersCount.value;
  const max = maxPositions.value;

  if (current === 0) return 'text-zinc-500 dark:text-zinc-400';
  if (current < max) return 'text-amber-600 dark:text-amber-400';
  if (current === max) return 'text-green-600 dark:text-green-400';
  return 'text-red-600 dark:text-red-400';
});

// Permissions
const page = usePage();
const canAssignMembers = computed(() => {
  return page.props.auth?.can?.['duties.update.padalinys'] || false;
});

const canManageDuty = computed(() => {
  return page.props.auth?.can?.['duties.update.padalinys'] || false;
});

// Event handlers
const handleEdit = () => {
  router.get(route("duties.edit", props.duty.id));
};

const handleDelete = () => {
  router.delete(route("duties.destroy", props.duty.id));
};

const handleEditDutyPeriod = (member: App.Entities.User) => {
  if (member.pivot?.id) {
    router.visit(route('dutiables.edit', member.pivot.id));
  }
};

// Breadcrumbs
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    props.duty.institution?.name,
    "institutions.show",
    { institution: props.duty?.institution?.id },
    props.duty.name,
    Icons.INSTITUTION,
    Icons.DUTY
  )
);
</script>
