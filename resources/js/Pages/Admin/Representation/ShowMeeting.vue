<template>
  <AdminContentPage>
    <InertiaHead :title="meetingTitle" />

    <!-- Meeting Hero Section -->
    <ShowPageHero
      :title="meetingTitle"
      :badge="meetingBadge"
    >
      <template #icon>
        <span class="text-base sm:text-lg font-medium text-zinc-600 dark:text-zinc-300">
          {{ formatStaticTime(new Date(meeting.start_time), { day: "numeric" }) }}
        </span>
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
          </div>
          <Badge v-if="meeting.type_label" variant="secondary" class="text-xs">
            {{ meeting.type_label }}
          </Badge>
          <Badge v-if="meeting.is_public" variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
            <Globe class="h-3 w-3" />
            <span class="hidden sm:inline">{{ $t('Rodomas viešai') }}</span>
            <span class="sm:hidden">{{ $t('Viešas') }}</span>
          </Badge>
          <!-- Protocol status -->
          <span
            v-if="isPastMeeting"
            class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400"
            :title="hasProtocol ? $t('Protokolas įkeltas') : $t('Protokolas neįkeltas')"
          >
            <svg
              class="h-4 w-4 shrink-0"
              :class="hasProtocol ? 'text-green-600 dark:text-green-400' : 'text-amber-500 dark:text-amber-400'"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <template v-if="hasProtocol">
                <path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4" />
                <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                <path d="M15 8h-5" />
                <path d="M15 12h-5" />
              </template>
              <template v-else>
                <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                <path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4" />
              </template>
            </svg>
            <span class="hidden sm:inline">{{ $t('Protokolas') }}</span>
          </span>
          <!-- Report status -->
          <span
            v-if="isPastMeeting"
            class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400"
            :title="hasReport ? $t('Ataskaita įkelta') : $t('Ataskaita neįkelta')"
          >
            <svg
              class="h-4 w-4 shrink-0"
              :class="hasReport ? 'text-green-600 dark:text-green-400' : 'text-amber-500 dark:text-amber-400'"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <template v-if="hasReport">
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                <path d="M8 18v-2" />
                <path d="M12 18v-4" />
                <path d="M16 18v-6" />
              </template>
              <template v-else>
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
              </template>
            </svg>
            <span class="hidden sm:inline">{{ $t('Ataskaita') }}</span>
          </span>
        </div>
        <div v-if="representatives && representatives.length > 0" class="flex items-center gap-2">
          <span class="text-xs text-zinc-500 dark:text-zinc-400 hidden sm:inline">{{ $t('Atstovai') }}:</span>
          <UsersAvatarGroup :users="representatives" :max="3" :size="24" class="sm:[--max:5]" />
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
      <TabsList class="gap-2 mb-4">
        <TabsTrigger value="overview">
          {{ $t('Apžvalga') }}
        </TabsTrigger>
        <TabsTrigger value="agenda" class="relative">
          {{ $t('Darbotvarkė') }}
          <span v-if="meeting.agenda_items?.length" class="ml-1.5 text-xs opacity-70">
            ({{ meeting.agenda_items.length }})
          </span>
          <SpotlightBadge v-if="showAgendaSpotlight" />
        </TabsTrigger>
        <TabsTrigger value="files">
          {{ $t('Failai') }}
        </TabsTrigger>
        <TabsTrigger value="tasks">
          {{ $t('Užduotys') }}
          <span v-if="meeting.tasks?.length" class="ml-1.5 text-xs opacity-70">
            ({{ meeting.tasks.length }})
          </span>
        </TabsTrigger>
      </TabsList>

      <!-- Overview Tab -->
      <TabsContent value="overview" class="space-y-6">
        <MeetingOverviewSection
          :meeting
          :previous-meeting
          :next-meeting
          @go-to-agenda="navigateToTab('agenda')"
          @go-to-agenda-item="navigateToAgendaItem"
          @go-to-files="navigateToTab('files')"
        />
      </TabsContent>

      <!-- Agenda Tab -->
      <TabsContent value="agenda">
        <SortableCardContainer
          ref="sortableContainerRef"
          :items="meeting.agenda_items ?? []"
          :meeting-id="meeting.id"
          @add="showSingleAgendaItemModal = true"
          @add-bulk="showAgendaItemStoreModal = true"
          @delete="handleAgendaItemDelete"
        />
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
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { router, useForm, Link, Head as InertiaHead } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, AlertCircle, Plus, Trash2, X, ChevronLeft, ChevronRight, Clock, Globe, Edit, MoreHorizontal, Video, Link2 } from 'lucide-vue-next';
import { DialogDescription } from 'reka-ui';

import { formatStaticTime } from '@/Utils/IntlTime';
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
import MeetingOverviewSection from '@/Components/Meetings/MeetingOverviewSection.vue';
import SortableCardContainer from '@/Components/AgendaItems/SortableCardContainer.vue';
import AddAgendaItemForm from '@/Components/AdminForms/AddAgendaItemForm.vue';
import AgendaItemsForm from '@/Components/AdminForms/Special/AgendaItemsForm.vue';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import ActivityLogButton from '@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue';
import SpotlightBadge from '@/Components/Onboarding/SpotlightBadge.vue';
import { InstitutionIconFilled, MeetingIconFilled } from '@/Components/icons';

const props = defineProps<{
  meeting: App.Entities.Meeting;
  representatives: App.Entities.User[];
  previousMeeting?: { id: string; start_time: string; type?: string | null } | null;
  nextMeeting?: { id: string; start_time: string; type?: string | null } | null;
  availableInstitutionsForAttach?: { id: string; name: string; tenant_shortname?: string | null }[] | null;
}>();

// Urgency calculations for hero badge
const { overallUrgency, hasProtocol, hasReport, isPastMeeting } = useMeetingUrgency(() => props.meeting);

// Hide HH:MM for email/electronic meetings (start_time is forced to 23:59 as a deadline marker)
const meetingTimeLabel = computed(() => formatMeetingTimeOnly(props.meeting));

// Component state
const showMeetingModal = ref(false);
const showAgendaItemStoreModal = ref(false);
const showSingleAgendaItemModal = ref(false);
const showDeleteDialog = ref(false);
const loading = ref(false);

// Tab state with smart defaults
const hasVisitedAgendaTab = useStorage('meeting-agenda-tab-visited', false);
const storedTab = useStorage('show-meeting-tab', 'overview');

// Check URL for tab parameter (priority over localStorage)
const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');
    if (urlTab && ['overview', 'agenda', 'files', 'tasks'].includes(urlTab)) {
      return urlTab;
    }
  }
  return storedTab.value;
};

const currentTab = ref(getInitialTab());

// Show spotlight on agenda tab for users who haven't visited it yet
const showAgendaSpotlight = computed(() => {
  return !hasVisitedAgendaTab.value && (props.meeting.agenda_items?.length ?? 0) > 0;
});

// Mark agenda tab as visited when user clicks on it, and sync to URL
watch(currentTab, (newTab) => {
  storedTab.value = newTab;
  if (newTab === 'agenda') {
    hasVisitedAgendaTab.value = true;
  }

  // Sync tab state to URL without page reload
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    if (newTab === 'overview') {
      url.searchParams.delete('tab');
      url.searchParams.delete('action');
    }
    else {
      url.searchParams.set('tab', newTab);
      // Clear action param when switching tabs normally
      url.searchParams.delete('action');
    }
    window.history.replaceState({}, '', url.toString());
  }
});

// Reset to overview for new meeting visits (unless URL specifies a tab)
onMounted(() => {
  const lastVisitedMeetingId = useStorage('last-visited-meeting-id', '');
  const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
  const urlTab = params?.get('tab');
  const urlAction = params?.get('action');

  // If no URL tab specified and it's a new meeting, reset to overview
  if (!urlTab && lastVisitedMeetingId.value !== props.meeting.id) {
    currentTab.value = 'overview';
  }
  lastVisitedMeetingId.value = props.meeting.id;

  // Auto-open agenda add modal if action=add and on agenda tab
  if (urlTab === 'agenda' && urlAction === 'add') {
    // Small delay to ensure component is mounted
    setTimeout(() => {
      showSingleAgendaItemModal.value = true;
      // Clear the action param from URL
      if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        window.history.replaceState({}, '', url.toString());
      }
    }, 100);
  }
});

// Navigation helper
const navigateToTab = (tab: string) => {
  currentTab.value = tab;
};

// Navigate to specific agenda item
const sortableContainerRef = ref<InstanceType<typeof SortableCardContainer> | null>(null);

const navigateToAgendaItem = (itemId: string) => {
  currentTab.value = 'agenda';
  // Use setTimeout to ensure the tab content is fully rendered before expanding
  setTimeout(() => {
    sortableContainerRef.value?.expandItem(itemId);
  }, 100);
};

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

const handleAgendaItemDelete = (agendaItem: App.Entities.AgendaItem) => {
  router.delete(route('agendaItems.destroy', agendaItem.id));
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
