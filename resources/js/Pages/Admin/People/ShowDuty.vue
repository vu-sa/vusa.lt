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
    <template v-if="hasFiles">
      <Tabs v-model="currentTab" class="space-y-6">
        <TabsList class="gap-2">
          <TabsTrigger value="members">
            {{ $t('Nariai') }}
          </TabsTrigger>
          <TabsTrigger value="files">
            {{ $t('Failai') }}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="members">
          <MemberHistory 
            :members="allMembers" 
            :show-contact="true" 
            :can-edit="canManageDuty"
            @edit-period="handleEditDutyPeriod" 
          />
        </TabsContent>

        <TabsContent value="files">
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
    </template>

    <!-- No tabs when no files - just show members -->
    <template v-else>
      <MemberHistory 
        :members="allMembers" 
        :show-contact="true" 
        :can-edit="canManageDuty"
        @edit-period="handleEditDutyPeriod" 
      />
    </template>

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
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import DutyHero from "@/Components/Duties/DutyHero.vue";
import MemberHistory from "@/Components/Members/MemberHistory.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";

// UI Components
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";

// Utils
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

const props = defineProps<{
  duty: App.Entities.Duty;
}>();

// State
const currentTab = useStorage("show-duty-tab", "members");
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

const hasFiles = computed(() => {
  return props.duty.types && props.duty.types.length > 0;
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
