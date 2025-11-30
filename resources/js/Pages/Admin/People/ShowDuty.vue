<template>
  <AdminContentPage>
    <InertiaHead :title="duty.name" />

    <!-- Duty Hero -->
    <DutyHero :duty :current-members="filteredUsers.currentUsers" :historical-members="filteredUsers.oldUsers"
      :can-assign-members :can-manage-duty @assign-member="showAssignMemberModal = true" @manage-duty="handleEdit">
      <template #actions>
        <MoreOptionsButton edit delete @edit-click="handleEdit" @delete-click="handleDelete" />
      </template>
    </DutyHero>

    <!-- Main Content -->
    <Tabs v-model="currentTab" class="space-y-6">
      <TabsList class="gap-2">
        <TabsTrigger value="overview">
          {{ $t('Apžvalga') }}
        </TabsTrigger>
        <TabsTrigger value="members">
          {{ $t('Nariai') }}
        </TabsTrigger>
        <TabsTrigger value="history">
          {{ $t('Istorija') }}
        </TabsTrigger>
        <TabsTrigger v-if="duty.types && duty.types.length > 0" value="files">
          {{ $t('Failai') }}
        </TabsTrigger>
      </TabsList>

      <TabsContent value="overview" class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="xl:col-span-2 space-y-6">
            <!-- Current Members -->
            <MemberGrid :title="$t('Dabar eina pareigas')"
              :subtitle="`${filteredUsers.currentUsers.length} / ${duty.places_to_occupy || 0} ${$t('pozicijų')}`"
              :members="filteredUsers.currentUsers" :max-positions="duty.places_to_occupy" :show-contact="true"
              :show-actions="true" :can-edit="canManageDuty" :can-add-member="canAssignMembers"
              :empty-title="$t('Pareigos neužimtos')"
              :empty-description="$t('Šių pareigų šiuo metu niekas neeina.')"
              @add-member="showAssignMemberModal = true" @view-profile="handleViewProfile"
              @edit-member="handleEditMember" />

            <!-- Empty Positions Visualization -->
            <Card v-if="emptyPositions > 0">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Users class="h-5 w-5" />
                  {{ $t('Laisvos pozicijos') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div v-for="(empty, index) in emptyPositions" :key="index"
                    class="flex items-center justify-center border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-4 text-center">
                    <div>
                      <UserPlus class="h-8 w-8 text-zinc-400 mx-auto mb-2" />
                      <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $t('Neužimta pozicija') }}
                      </span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Institution Context -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Building class="h-5 w-5" />
                  {{ $t('Institucija') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="space-y-3">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                      <Building class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                      <p class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ duty.institution?.name || $t('Nežinoma institucija') }}
                      </p>
                      <p v-if="duty.institution?.short_name" class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ duty.institution.short_name }}
                      </p>
                    </div>
                  </div>
                  <Button v-if="duty.institution" variant="outline" size="sm" class="w-full"
                    @click="router.visit(route('institutions.show', duty.institution.id))">
                    {{ $t('Peržiūrėti instituciją') }}
                  </Button>
                </div>
              </CardContent>
            </Card>

            <!-- Contact Info -->
            <Card v-if="duty.email" class="h-24">
              <CardHeader>
                <CardTitle>{{ $t('Kontaktai') }}</CardTitle>
              </CardHeader>
              <CardContent>
              </CardContent>
            </Card>
          </div>
        </div>
      </TabsContent>

      <TabsContent value="members">
        <MemberGrid :title="$t('Visi einantys pareigas')"
          :subtitle="`${filteredUsers.currentUsers.length} ${$t('aktyvūs nariai')}`"
          :members="filteredUsers.currentUsers" :max-positions="duty.places_to_occupy" :show-contact="true"
          :show-actions="true" :can-edit="canManageDuty" :can-add-member="canAssignMembers"
          @add-member="showAssignMemberModal = true" @view-profile="handleViewProfile"
          @edit-member="handleEditMember" />
      </TabsContent>

      <TabsContent value="history">
        <MemberTimeline :members="allMembers" :show-contact="true" :show-actions="true"
          @view-profile="handleViewProfile" />
      </TabsContent>

      <TabsContent v-if="duty.types && duty.types.length > 0" value="files">
        <Suspense>
          <SimpleFileViewer :fileable="{ id: duty.id, type: 'Duty' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              {{ $t('Kraunami susiję failai...') }}
            </div>
          </template>
        </Suspense>
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
import { router, Head as InertiaHead, usePage } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";

// Layout and Components
import { Users, UserPlus, Building, Mail } from 'lucide-vue-next';

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import DutyHero from "@/Components/Duties/DutyHero.vue";
import MemberGrid from "@/Components/Members/MemberGrid.vue";
import MemberTimeline from "@/Components/Members/MemberTimeline.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";

// UI Components
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";

// Icons

// Utils
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

const props = defineProps<{
  duty: App.Entities.Duty;
}>();

// State
const currentTab = useStorage("show-duty-tab", "overview");
const showAssignMemberModal = ref(false);

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

const emptyPositions = computed(() => {
  const maxPositions = props.duty.places_to_occupy || 0;
  const currentCount = filteredUsers.value.currentUsers.length;
  return Math.max(0, maxPositions - currentCount);
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

const handleViewProfile = (member: App.Entities.User) => {
  router.visit(route('users.show', member.id));
};

const handleEditMember = (member: App.Entities.User) => {
  router.visit(route('users.edit', member.id));
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
