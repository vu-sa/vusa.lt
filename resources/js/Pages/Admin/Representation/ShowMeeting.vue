<template>
  <AdminContentPage>
    <InertiaHead :title="meetingTitle" />

    <!-- Meeting Hero Section -->
    <ShowPageHero
      :title="meetingTitle"
      :badge="meetingBadge"
    >
      <template #icon>
        <div class="flex flex-col items-center justify-center leading-none">
          <span class="text-lg sm:text-xl font-semibold text-zinc-700 dark:text-zinc-200">
            {{ formatStaticTime(new Date(meeting.start_time), { day: "numeric" }) }}
          </span>
          <span class="mt-0.5 text-[10px] font-medium tracking-wide text-zinc-400 dark:text-zinc-500">
            {{ formatMonthShort(new Date(meeting.start_time)) }}
          </span>
        </div>
      </template>
      <template #subtitle>
        <!-- Joint meeting institution management (unobtrusive) -->
        <div v-if="meeting.institutions && meeting.institutions.length > 0" class="flex flex-wrap mt-1 items-center gap-2">
          <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $t('Institucijos') }}:</span>
          <div v-for="institution in meeting.institutions" :key="institution.id" class="flex items-center gap-0.5">
            <Badge variant="outline" class="text-xs">
              {{ institution.name }}
            </Badge>
            <button
              v-if="(meeting.institutions?.length ?? 0) > 1"
              type="button"
              class="flex items-center justify-center h-4 w-4 rounded text-zinc-400 hover:text-destructive hover:bg-destructive/10 transition-colors"
              :title="$t('Pašalinti instituciją')"
              @click="handleDetachInstitution(institution.id)"
            >
              <X class="h-2.5 w-2.5" />
            </button>
          </div>
          <button
            type="button"
            class="flex items-center gap-1 text-xs text-zinc-400 hover:text-primary transition-colors"
            :title="$t('Pridėti instituciją')"
            @click="showAddInstitutionDialog = true"
          >
            <Plus class="h-3 w-3" />
            <span class="hidden sm:inline">{{ $t('Pridėti instituciją') }}</span>
          </button>
        </div>
      </template>
      <template #info>
        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-sm">
          <div v-if="meetingTimeLabel" class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
            <Clock class="h-4 w-4 text-green-500 shrink-0" />
            <span>{{ meetingTimeLabel }}</span>
            <span class="text-zinc-400 dark:text-zinc-500">· {{ meetingRelativeTime }}</span>
          </div>
          <Badge v-if="meeting.type_label" variant="secondary" class="text-xs">
            {{ meeting.type_label }}
          </Badge>
          <Badge v-if="meeting.is_public" variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
            <Globe class="h-3 w-3" />
            <span class="hidden sm:inline">{{ $t('Rodomas viešai') }}</span>
            <span class="sm:hidden">{{ $t('Viešas') }}</span>
          </Badge>
        </div>
        <div v-if="representatives && representatives.length > 0" class="flex items-center gap-2">
          <span class="text-xs text-zinc-500 dark:text-zinc-400 hidden sm:inline">{{ $t('Atstovai') }}:</span>
          <UsersAvatarGroup :users="representatives" :max="4" :size="24" expandable />
        </div>
      </template>
      <template #actions>
        <ActivityLogButton :activities="meeting.activities ?? []" />
        <Button variant="outline" size="icon" class="h-9 w-9" @click="showMeetingModal = true">
          <Edit class="h-4 w-4" />
        </Button>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="icon" class="h-9 w-9">
              <MoreHorizontal class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="showMeetingModal = true">
              <Edit class="h-4 w-4 mr-2" />
              {{ $t('Redaguoti posėdį') }}
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem class="text-destructive focus:text-destructive" @click="showDeleteDialog = true">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t('Šalinti posėdį') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </template>
    </ShowPageHero>

    <!-- Tabs Navigation -->
    <Tabs v-model="currentTab" class="mt-6">
      <div class="mb-4 flex items-center justify-between gap-4">
        <TabsList class="h-10 gap-1 rounded-xl bg-zinc-100/80 p-1 dark:bg-zinc-800/60">
          <TabsTrigger
            value="agenda"
            class="rounded-lg px-3.5 data-[state=active]:shadow-sm data-[state=active]:font-semibold"
          >
            {{ $t('Darbotvarkė') }}
            <span v-if="meeting.agenda_items?.length" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
              {{ meeting.agenda_items.length }}
            </span>
          </TabsTrigger>
          <TabsTrigger
            value="files"
            class="rounded-lg px-3.5 data-[state=active]:shadow-sm data-[state=active]:font-semibold"
          >
            {{ $t('Failai') }}
          </TabsTrigger>
          <TabsTrigger
            value="tasks"
            class="rounded-lg px-3.5 data-[state=active]:shadow-sm data-[state=active]:font-semibold"
          >
            {{ $t('Užduotys') }}
            <span v-if="meeting.tasks?.length" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
              {{ meeting.tasks.length }}
            </span>
          </TabsTrigger>
        </TabsList>

        <!-- Protocol / report status (past meetings only) -->
        <div v-if="isPastMeeting" class="hidden sm:flex items-center gap-3">
          <span
            class="inline-flex items-center gap-1.5 text-xs"
            :class="hasProtocol ? 'text-green-600 dark:text-green-400' : 'text-zinc-400 dark:text-zinc-500'"
            :title="hasProtocol ? $t('Protokolas įkeltas') : $t('Protokolas neįkeltas')"
          >
            <FileText class="h-4 w-4 shrink-0" />
            {{ $t('Protokolas') }}
            <Check v-if="hasProtocol" class="h-3.5 w-3.5" />
          </span>
          <span
            class="inline-flex items-center gap-1.5 text-xs"
            :class="hasReport ? 'text-green-600 dark:text-green-400' : 'text-zinc-400 dark:text-zinc-500'"
            :title="hasReport ? $t('Ataskaita įkelta') : $t('Ataskaita neįkelta')"
          >
            <FileBarChart class="h-4 w-4 shrink-0" />
            {{ $t('Ataskaita') }}
            <Check v-if="hasReport" class="h-3.5 w-3.5" />
          </span>
        </div>
      </div>

      <!-- Agenda Tab -->
      <TabsContent value="agenda" class="space-y-6">
        <MeetingAgendaList
          v-model:editing="agendaEditing"
          :agenda-items="meeting.agenda_items ?? []"
          :meeting-id="meeting.id"
          @add="showSingleAgendaItemModal = true"
          @add-bulk="showAgendaItemStoreModal = true"
          @delete="requestAgendaItemDelete"
        />
        <MeetingNavigationCards
          v-if="previousMeeting || nextMeeting"
          :previous-meeting="previousMeeting"
          :next-meeting="nextMeeting"
        />

        <!-- Meeting-level discussion lives directly under the agenda. -->
        <section class="border-t pt-6 dark:border-zinc-800">
          <DiscussionPanel commentable-type="meeting" :commentable-id="meeting.id" />
        </section>
      </TabsContent>

      <!-- Files Tab -->
      <TabsContent value="files" class="space-y-6">
        <FileManager :starting-path="meeting.sharepointPath" :fileable="{ id: meeting.id, type: 'Meeting' }" />
      </TabsContent>

      <!-- Tasks Tab -->
      <TabsContent value="tasks" class="space-y-6">
        <TaskManager :taskable="{ id: meeting.id, type: 'App\\Models\\Meeting' }" :tasks="meeting.tasks" />
      </TabsContent>
    </Tabs>

    <!-- Modals -->
    <Dialog v-model:open="showMeetingModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t("Redaguoti posėdžio datą") }}</DialogTitle>
        </DialogHeader>
        <Suspense>
          <MeetingForm class="mt-2" :meeting @submit="handleMeetingFormSubmit" />
        </Suspense>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showSingleAgendaItemModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Plus class="h-5 w-5" />
            {{ $t("Pridėti darbotvarkės punktą") }}
          </DialogTitle>
        </DialogHeader>
        <AddAgendaItemForm :meeting-id="meeting.id" :loading @submit="handleSingleAgendaItemSubmit" />
        <div class="mt-4 pt-4 border-t">
          <Button variant="outline" size="sm" class="w-full" @click="showSingleAgendaItemModal = false; showAgendaItemStoreModal = true;">
            {{ $t("Pridėti kelis punktus iš karto") }}...
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showAgendaItemStoreModal">
      <DialogContent class="max-h-[85vh] flex flex-col">
        <DialogHeader class="flex-none">
          <DialogTitle>{{ $t("Pridėti darbotvarkės punktus") }}</DialogTitle>
        </DialogHeader>
        <div class="flex-1 overflow-y-auto -mx-6 px-6">
          <AgendaItemsForm
            class="w-full"
            :loading
            mode="add"
            :submit-label="$t('Pridėti punktus')"
            :show-skip-button="false"
            @submit="handleAgendaItemsFormSubmit"
          />
        </div>
      </DialogContent>
    </Dialog>

    <!-- Add Institution Dialog -->
    <Dialog v-model:open="showAddInstitutionDialog">
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Link2 class="h-4 w-4" />
            {{ $t('Pridėti instituciją') }}
          </DialogTitle>
          <DialogDescription class="text-sm text-zinc-500 mt-2">
            Retais atvejais, atstovavimo organai gali turėti bendrų posėdžių. Jei šis posėdis yra bendras su kitomis institucijomis, pasirinkite jas.
            Galite pasirinkti tik iš susijusių institucijų.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 pt-2">
          <Select v-model="addInstitutionId">
            <SelectTrigger>
              <SelectValue :placeholder="$t('Pasirinkite instituciją')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="option in availableInstitutionsToAdd"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="availableInstitutionsToAdd.length === 0" class="text-sm text-zinc-500">
            {{ $t('Nėra galimų institucijų pridėti.') }}
          </p>
          <div class="flex justify-end gap-2">
            <Button variant="outline" size="sm" @click="showAddInstitutionDialog = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button size="sm" :disabled="!addInstitutionId" @click="handleAttachInstitution">
              {{ $t('Pridėti') }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Agenda Item Delete Confirmation -->
    <Dialog v-model:open="showAgendaItemDeleteDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-destructive">
            <AlertTriangle class="h-5 w-5" />
            {{ $t("Šalinti darbotvarkės punktą?") }}
          </DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $t("Ar tikrai norite ištrinti šį darbotvarkės punktą? Šis veiksmas negrįžtamas.") }}
          </p>
          <p v-if="agendaItemPendingDelete" class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ agendaItemPendingDelete.title }}
          </p>
          <div class="flex justify-end gap-3">
            <Button variant="outline" @click="showAgendaItemDeleteDialog = false">
              {{ $t("Atšaukti") }}
            </Button>
            <Button variant="destructive" @click="confirmAgendaItemDelete">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t("Šalinti") }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-destructive">
            <AlertTriangle class="h-5 w-5" />
            {{ $t("Šalinti posėdį?") }}
          </DialogTitle>
        </DialogHeader>

        <div class="space-y-4">
          <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $t("Ar tikrai norite ištrinti šį posėdį? Šis veiksmas negrįžtamas ir bus pašalinti visi su posėdžiu susiję duomenys, įskaitant darbotvarkės punktus.") }}
          </p>

          <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/50 rounded-lg p-3">
            <p class="text-sm text-red-800 dark:text-red-300 font-medium">
              {{ $t("Bus ištrinta:") }}
            </p>
            <ul class="text-xs text-red-700 dark:text-red-400 mt-1 space-y-1">
              <li>• {{ meeting.agenda_items?.length ?? 0 }} {{ $t("darbotvarkės punktai") }}</li>
              <li v-if="meeting.tasks && meeting.tasks.length">
                • {{ meeting.tasks.length }} {{ $t("užduotys") }}
              </li>
              <li v-if="meeting.files && meeting.files.length">
                • {{ meeting.files.length }} {{ $t("failai") }}
              </li>
            </ul>
          </div>

          <div class="flex justify-end gap-3">
            <Button variant="outline" @click="showDeleteDialog = false">
              {{ $t("Atšaukti") }}
            </Button>
            <Button variant="destructive" @click="handleMeetingDelete">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t("Šalinti posėdį") }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { ref, computed, watch, onMounted } from 'vue';
import { router, useForm, Head as InertiaHead } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, Plus, Trash2, X, Clock, Globe, Edit, MoreHorizontal, Video, Link2, Check, FileText, FileBarChart } from 'lucide-vue-next';
import { DialogDescription } from 'reka-ui';

import { formatStaticTime, formatMonthShort, formatRelativeTime } from '@/Utils/IntlTime';
import { formatMeetingDateTime, formatMeetingTimeOnly } from '@/Utils/MeetingDisplay';
import { genitivizeEveryWord } from '@/Utils/String';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useMeetingUrgency } from '@/Composables/useMeetingUrgency';

// Layout
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';

// UI Components
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

// Custom Components
import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import MeetingAgendaList from '@/Components/Meetings/MeetingAgendaList.vue';
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import MeetingNavigationCards from '@/Components/Meetings/MeetingNavigationCards.vue';
import AddAgendaItemForm from '@/Components/AdminForms/AddAgendaItemForm.vue';
import AgendaItemsForm from '@/Components/AdminForms/Special/AgendaItemsForm.vue';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import ActivityLogButton from '@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue';
import { InstitutionIconFilled, MeetingIconFilled } from '@/Components/icons';

const props = defineProps<{
  meeting: App.Entities.Meeting;
  representatives: App.Entities.User[];
  previousMeeting?: { id: string; start_time: string; type?: string | null } | null;
  nextMeeting?: { id: string; start_time: string; type?: string | null } | null;
  availableInstitutionsForAttach?: { id: string; name: string; tenant_shortname?: string | null }[] | null;
}>();

// Urgency calculations for hero badge
const { hasProtocol, hasReport, isPastMeeting } = useMeetingUrgency(() => props.meeting);

// Hide HH:MM for email/electronic meetings (start_time is forced to 23:59 as a deadline marker)
const meetingTimeLabel = computed(() => formatMeetingTimeOnly(props.meeting));
const meetingRelativeTime = computed(() => formatRelativeTime(new Date(props.meeting.start_time)));

// Component state
const showMeetingModal = ref(false);
const showAgendaItemStoreModal = ref(false);
const showSingleAgendaItemModal = ref(false);
const showDeleteDialog = ref(false);
const loading = ref(false);

// Read-only by default; toggled on to add/reorder/delete agenda items
const agendaEditing = ref(false);

// Tab state with smart defaults (agenda is the landing tab)
const TAB_NAMES = ['agenda', 'files', 'tasks'];
const storedTab = useStorage('show-meeting-tab', 'agenda');

// Check URL for tab parameter (priority over localStorage)
const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');
    if (urlTab && TAB_NAMES.includes(urlTab)) {
      return urlTab;
    }
  }
  return TAB_NAMES.includes(storedTab.value) ? storedTab.value : 'agenda';
};

const currentTab = ref(getInitialTab());

// Sync tab state to URL without page reload
watch(currentTab, (newTab) => {
  storedTab.value = newTab;

  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    if (newTab === 'agenda') {
      url.searchParams.delete('tab');
    }
    else {
      url.searchParams.set('tab', newTab);
    }
    url.searchParams.delete('action');
    window.history.replaceState({}, '', url.toString());
  }
});

onMounted(() => {
  const lastVisitedMeetingId = useStorage('last-visited-meeting-id', '');
  const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
  const urlTab = params?.get('tab');
  const urlAction = params?.get('action');

  // Reset to the agenda tab for a freshly visited meeting (unless URL pins a tab)
  if (!urlTab && lastVisitedMeetingId.value !== props.meeting.id) {
    currentTab.value = 'agenda';
  }
  lastVisitedMeetingId.value = props.meeting.id;

  // Auto-open the add dialog in edit mode if action=add
  if (urlAction === 'add') {
    currentTab.value = 'agenda';
    setTimeout(() => {
      agendaEditing.value = true;
      showSingleAgendaItemModal.value = true;
      if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        window.history.replaceState({}, '', url.toString());
      }
    }, 100);
  }
});

// Form handling
const meetingAgendaForm = useForm({
  meeting: props.meeting.id,
  agendaItems: [],
});

// Computed values
const mainInstitution: App.Entities.Institution | string
  = props.meeting.institutions?.[0] ?? 'Be institucijos';

const isJoint = computed(
  () => (props.meeting as any).is_joint ?? (props.meeting.institutions?.length ?? 0) > 1,
);

// Always derive the displayed title from start_time + institution.
// The stored `meeting.title` is auto-generated server-side and historically
// embedded a 23.59 timestamp for email meetings — recomputing here keeps the
// header/breadcrumb correct without a data backfill.
const meetingTitle = computed(() => {
  const datePart = formatMeetingDateTime(props.meeting, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  if (isJoint.value) {
    return `${datePart} jungtinis posėdis`;
  }

  const institutionName = typeof mainInstitution === 'string'
    ? mainInstitution
    : mainInstitution.name;

  return `${datePart} ${genitivizeEveryWord(institutionName)} posėdis`;
});

// Hero subtitle - institution name(s) with link
const heroSubtitle = computed(() => {
  if (isJoint.value) {
    return props.meeting.institutions?.map((i: App.Entities.Institution) => i.name).join(' · ') ?? '';
  }
  if (typeof mainInstitution === 'string') {
    return mainInstitution;
  }
  return mainInstitution.name;
});

// Badge for meeting type
const meetingBadge = computed(() => ({
  label: $t('Posėdis'),
  variant: 'secondary' as const,
  icon: Video,
}));

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() => {
  if (typeof mainInstitution === 'string') {
    return [
      { label: mainInstitution, icon: InstitutionIconFilled },
      { label: meetingTitle.value, icon: MeetingIconFilled },
    ];
  }

  return BreadcrumbHelpers.adminShow(
    mainInstitution.name,
    'institutions.show',
    { institution: mainInstitution.id },
    meetingTitle.value,
    InstitutionIconFilled,
    MeetingIconFilled,
  );
});

// Joint meeting — institution management
const showAddInstitutionDialog = ref(false);
const addInstitutionId = ref('');

const availableInstitutionsToAdd = computed(() => {
  const attached = new Set(props.meeting.institutions?.map((i: App.Entities.Institution) => i.id) ?? []);
  return (props.availableInstitutionsForAttach ?? [])
    .filter(inst => !attached.has(inst.id))
    .map(inst => ({
      label: inst.tenant_shortname ? `${inst.name} (${inst.tenant_shortname})` : inst.name,
      value: inst.id,
    }));
});

const handleAttachInstitution = () => {
  if (!addInstitutionId.value) { return; }
  router.post(route('meetings.institutions.attach', props.meeting.id), {
    institution_id: addInstitutionId.value,
  }, {
    onSuccess: () => {
      showAddInstitutionDialog.value = false;
      addInstitutionId.value = '';
    },
  });
};

const handleDetachInstitution = (institutionId: string) => {
  router.delete(route('meetings.institutions.detach', { meeting: props.meeting.id, institution: institutionId }));
};

// Event handlers
const handleMeetingFormSubmit = (meeting: App.Entities.Meeting) => {
  router.patch(route('meetings.update', props.meeting.id), meeting, {
    onSuccess: () => {
      showMeetingModal.value = false;
    },
  });
};

// Agenda item deletion goes through a confirmation dialog
const showAgendaItemDeleteDialog = ref(false);
const agendaItemPendingDelete = ref<App.Entities.AgendaItem | null>(null);

const requestAgendaItemDelete = (agendaItem: App.Entities.AgendaItem) => {
  agendaItemPendingDelete.value = agendaItem;
  showAgendaItemDeleteDialog.value = true;
};

const confirmAgendaItemDelete = () => {
  if (!agendaItemPendingDelete.value) { return; }
  router.delete(route('agendaItems.destroy', agendaItemPendingDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showAgendaItemDeleteDialog.value = false;
      agendaItemPendingDelete.value = null;
    },
  });
};

const handleSingleAgendaItemSubmit = (data: { meeting_id: string; title: string; description?: string; brought_by_students?: boolean }) => {
  loading.value = true;

  router.post(route('agendaItems.store'), {
    meeting_id: data.meeting_id,
    agendaItemTitles: [data.title],
    agendaItemDescriptions: data.description ? [data.description] : [],
    broughtByStudentsFlags: [data.brought_by_students || false],
  }, {
    onSuccess: () => {
      showSingleAgendaItemModal.value = false;
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};

const handleAgendaItemsFormSubmit = (agendaItems: Record<string, any>) => {
  loading.value = true;

  meetingAgendaForm
    .transform(data => ({
      meeting_id: props.meeting.id,
      ...agendaItems,
    }))
    .post(route('agendaItems.store'), {
      onSuccess: () => {
        meetingAgendaForm.reset();
        showAgendaItemStoreModal.value = false;
      },
      onFinish: () => {
        loading.value = false;
      },
    });
};

const handleMeetingDelete = () => {
  const redirectTo = typeof mainInstitution === 'string'
    ? route('admin.dashboard')
    : route('institutions.show', mainInstitution.id);

  router.delete(route('meetings.destroy', props.meeting.id), {
    data: { redirect_to: redirectTo },
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};
</script>
