<template>
  <ShowPageLayout
    :title="duty.name"
    :subtitle="duty.institution?.name"
    :model="duty"
    audit-subject-type="duty"
    :tabs
    tab-storage-key="show-duty-tab"
  >
    <template #title>
      <InflectedDutyName :name="duty.name" />
    </template>
    <template #icon>
      <DutyIconFilled class="h-6 w-6 sm:h-7 sm:w-7 text-zinc-600 dark:text-zinc-300" />
    </template>
    <template #badge>
      <Badge
        v-if="isVacant"
        variant="outline"
        class="gap-1 text-xs text-amber-600 border-amber-300 dark:text-amber-400 dark:border-amber-700"
      >
        <UserX class="h-3 w-3" />
        {{ $t('Neužimta') }}
      </Badge>
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

    <template #alert>
      <PriorityAlert
        v-if="isVacant"
        v-model="showVacancyAlert"
        variant="warning"
        class="mt-4"
        :title="$t('Pareigos neužimtos')"
        :description="$t('Šiuo metu niekas neeina šių pareigų. Priskirkite narį, kad atnaujintumėte sudėtį.')"
        :action-label="canAssignMembers ? $t('Priskirti narį') : undefined"
        @action="showAssignMemberModal = true"
      />
    </template>

    <!-- Overview Tab: two-column dashboard -->
    <template #overview>
      <ShowPageGrid>
        <template #main>
          <DutyCurrentHoldersCard
            :holders="currentHolders"
            :places-to-occupy="duty.places_to_occupy ?? 0"
            :can-assign="canAssignMembers"
            @assign="showAssignMemberModal = true"
          />

          <DutyAboutCard
            v-if="hasAbout"
            :description
          />

          <DutyLineageCard v-if="allMembers.length > 0" :members="allMembers" />
        </template>

        <template #sidebar>
          <!-- Context tiles cluster together with tighter spacing -->
          <div v-if="duty.institution || duty.next_meeting || duty.last_meeting" class="space-y-2">
            <DutyInstitutionCard v-if="duty.institution" :institution="duty.institution" />
            <DutyMeetingMiniCard
              v-if="duty.next_meeting"
              :meeting="duty.next_meeting"
              :label="$t('Kitas posėdis')"
            />
            <DutyMeetingMiniCard
              v-if="duty.last_meeting"
              :meeting="duty.last_meeting"
              :label="$t('Paskutinis posėdis')"
            />
          </div>

          <DutyOtherDutiesCard v-if="otherDuties.length > 0" :duties="otherDuties" />

          <FileablePreviewCard
            v-if="hasTypeFiles"
            :fileable="{ id: duty.id, type: 'Duty' }"
          />
        </template>
      </ShowPageGrid>
    </template>

    <!-- Files Tab -->
    <template #files>
      <div class="space-y-6">
        <!-- Direct Duty Files (with upload capability) -->
        <div v-if="duty.sharepointPath">
          <h3 class="mb-4 text-lg font-medium">
            {{ $t('Pareigybės failai') }}
          </h3>
          <FileManager :starting-path="duty.sharepointPath" :fileable="{ id: duty.id, type: 'Duty' }" />
        </div>

        <!-- Type-inherited Files (read-only) -->
        <div v-if="hasTypeFiles">
          <h3 class="mb-4 text-lg font-medium">
            {{ $t('Susiję failai pagal tipą') }}
          </h3>
          <p class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
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
        <EmptyState
          v-if="!duty.sharepointPath && !hasTypeFiles"
          :title="$t('Failų nėra')"
          :description="$t('Pareigybė neturi failų ir nėra priskirta tipams su failais.')"
        >
          <template #icon>
            <FolderOpen class="h-10 w-10 text-muted-foreground" />
          </template>
        </EmptyState>
      </div>
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
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { UserPlus, Settings, Mail, UserX, FolderOpen } from 'lucide-vue-next';

// Layout and Components
import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import PriorityAlert from '@/Components/Alerts/PriorityAlert.vue';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import SimpleFileViewer from '@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue';
import { EmptyState, ShowPageGrid } from '@/Components/Patterns';
import { FileablePreviewCard } from '@/Components/Files';
import {
  DutyCurrentHoldersCard,
  DutyAboutCard,
  DutyLineageCard,
  DutyInstitutionCard,
  DutyMeetingMiniCard,
  DutyOtherDutiesCard,
} from '@/Components/Duties';
import type { OtherDuty } from '@/Components/Duties/DutyOtherDutiesCard.vue';
import type { MiniMeeting } from '@/Components/Duties/DutyMeetingMiniCard.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';

// UI Components
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

// Utils
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { DutyIconFilled, InstitutionIconFilled } from '@/Components/icons';

const props = defineProps<{
  duty: App.Entities.Duty & {
    sharepointPath?: string | null;
    other_duties?: OtherDuty[];
    next_meeting?: MiniMeeting | null;
    last_meeting?: MiniMeeting | null;
  };
}>();

const tabs = computed(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'files', label: $t('Failai') },
]);

const showAssignMemberModal = ref(false);
const showVacancyAlert = ref(true);

// Members split into current / historical via the dutiable pivot dates.
const filteredUsers = computed(() => {
  if (!props.duty.users) {
    return { currentUsers: [] as App.Entities.User[], oldUsers: [] as App.Entities.User[] };
  }

  return props.duty.users.reduce(
    (acc, user: App.Entities.User) => {
      if (!user.pivot) {
        return acc;
      }
      const end = user.pivot.end_date;
      if (end === null || end === undefined || new Date(end) >= new Date()) {
        acc.currentUsers.push(user);
      }
      else {
        acc.oldUsers.push(user);
      }
      return acc;
    },
    { currentUsers: [] as App.Entities.User[], oldUsers: [] as App.Entities.User[] },
  );
});

const currentHolders = computed(() => filteredUsers.value.currentUsers);
const allMembers = computed(() => [...filteredUsers.value.currentUsers, ...filteredUsers.value.oldUsers]);
const isVacant = computed(() => currentHolders.value.length === 0);

const hasTypeFiles = computed(() => (props.duty.types?.length ?? 0) > 0);

// Localized translatable strings (server returns them via toArray()).
const description = computed(() => (typeof props.duty.description === 'string' ? props.duty.description : null));
const hasAbout = computed(() => !!description.value);

const otherDuties = computed<OtherDuty[]>(() => props.duty.other_duties ?? []);

// Permissions
const page = usePage();
const canAssignMembers = computed(() => page.props.auth?.can?.['duties.update.padalinys'] || false);
const canManageDuty = computed(() => page.props.auth?.can?.['duties.update.padalinys'] || false);

// Event handlers
const handleEdit = () => {
  router.get(route('duties.edit', props.duty.id));
};

const handleDelete = () => {
  router.delete(route('duties.destroy', props.duty.id));
};

// Breadcrumbs
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    props.duty.institution?.name,
    'institutions.show',
    { institution: props.duty?.institution?.id },
    props.duty.name,
    InstitutionIconFilled,
    DutyIconFilled,
  ),
);
</script>
