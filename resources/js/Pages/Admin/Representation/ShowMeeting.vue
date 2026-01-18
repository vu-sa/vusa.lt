<template>
  <AdminContentPage>
    <InertiaHead :title="meetingTitle" />

    <!-- Meeting Hero Section -->
    <ShowPageHero
      :title="meetingTitle"
      :subtitle="heroSubtitle"
      :badge="meetingBadge"
    >
      <template #icon>
        <span class="text-base sm:text-lg font-medium text-zinc-600 dark:text-zinc-300">
          {{ formatStaticTime(new Date(meeting.start_time), { day: "numeric" }) }}
        </span>
      </template>
      <template #info>
        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-sm">
          <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
            <Clock class="h-4 w-4 text-green-500 shrink-0" />
            <span>{{ formatStaticTime(new Date(meeting.start_time), { hour: "2-digit", minute: "2-digit" }) }}</span>
          </div>
          <div v-if="meeting.types && meeting.types.length > 0" class="flex flex-wrap gap-1.5">
            <Badge v-for="type in meeting.types" :key="type.id" variant="secondary" class="text-xs">
              {{ type.title }}
            </Badge>
          </div>
          <Badge v-if="meeting.is_public" variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
            <Globe class="h-3 w-3" />
            <span class="hidden sm:inline">{{ $t('Rodomas viešai') }}</span>
            <span class="sm:hidden">{{ $t('Viešas') }}</span>
          </Badge>
          <!-- Urgency badge based on document status -->
          <Badge
            v-if="overallUrgency !== 'neutral' && overallUrgency !== 'success'"
            :variant="overallUrgency === 'danger' ? 'destructive' : 'secondary'"
            class="text-xs gap-1"
          >
            <AlertTriangle v-if="overallUrgency === 'danger'" class="h-3 w-3" />
            <AlertCircle v-else class="h-3 w-3" />
            <span class="hidden sm:inline">{{ overallUrgency === 'danger' ? $t('Trūksta dokumentų') : $t('Laukia užduotys') }}</span>
            <span class="sm:hidden">!</span>
          </Badge>
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
      <TabsList class="gap-2">
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
          :representatives
          :activities="meeting.activities"
          :previous-meeting
          :next-meeting
          @go-to-agenda="navigateToTab('agenda')"
          @go-to-files="navigateToTab('files')"
          @go-to-tasks="navigateToTab('tasks')"
          @edit="showMeetingModal = true"
        />
      </TabsContent>

      <!-- Agenda Tab -->
      <TabsContent value="agenda">
        <SortableCardContainer 
          :items="meeting.agenda_items ?? []" 
          :meeting-id="meeting.id"
          @add="showSingleAgendaItemModal = true" 
          @add-bulk="showAgendaItemStoreModal = true" 
          @edit="handleAgendaClick" 
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

    <Dialog v-model:open="showAgendaItemUpdateModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t("Redaguoti darbotvarkės punktą") }}</DialogTitle>
        </DialogHeader>
        <AgendaItemForm v-if="selectedAgendaItem" :agenda-item="selectedAgendaItem" @submit="handleAgendaItemUpdate" />
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
import { ref, computed, watch, onMounted } from "vue";
import { router, useForm, Link, Head as InertiaHead } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";
import { AlertTriangle, AlertCircle, Plus, Trash2, ChevronLeft, ChevronRight, Clock, Globe, Edit, MoreHorizontal, Video } from 'lucide-vue-next';

import { formatStaticTime } from "@/Utils/IntlTime";
import { genitivizeEveryWord } from "@/Utils/String";
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import { useMeetingUrgency } from "@/Composables/useMeetingUrgency";

// Layout
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";

// UI Components
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";

// Custom Components
import ShowPageHero from "@/Components/Hero/ShowPageHero.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import MeetingOverviewSection from "@/Components/Meetings/MeetingOverviewSection.vue";
import SortableCardContainer from "@/Components/AgendaItems/SortableCardContainer.vue";
import AgendaItemForm from "@/Components/AdminForms/AgendaItemForm.vue";
import AddAgendaItemForm from "@/Components/AdminForms/AddAgendaItemForm.vue";
import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/SharepointFileManager.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import SpotlightBadge from "@/Components/Onboarding/SpotlightBadge.vue";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  representatives: App.Entities.User[];
  previousMeeting?: { id: string; start_time: string } | null;
  nextMeeting?: { id: string; start_time: string } | null;
}>();

// Urgency calculations for hero badge
const { overallUrgency } = useMeetingUrgency(() => props.meeting);

// Component state
const showMeetingModal = ref(false);
const showAgendaItemStoreModal = ref(false);
const showSingleAgendaItemModal = ref(false);
const showAgendaItemUpdateModal = ref(false);
const showDeleteDialog = ref(false);
const loading = ref(false);

// Tab state with smart defaults
const hasVisitedAgendaTab = useStorage("meeting-agenda-tab-visited", false);
const storedTab = useStorage("show-meeting-tab", "overview");
const currentTab = ref(storedTab.value);

// Show spotlight on agenda tab for users who haven't visited it yet
const showAgendaSpotlight = computed(() => {
  return !hasVisitedAgendaTab.value && (props.meeting.agenda_items?.length ?? 0) > 0;
});

// Mark agenda tab as visited when user clicks on it
watch(currentTab, (newTab) => {
  storedTab.value = newTab;
  if (newTab === 'agenda') {
    hasVisitedAgendaTab.value = true;
  }
});

// Reset to overview for new meeting visits
onMounted(() => {
  const lastVisitedMeetingId = useStorage("last-visited-meeting-id", "");
  if (lastVisitedMeetingId.value !== props.meeting.id) {
    currentTab.value = "overview";
    lastVisitedMeetingId.value = props.meeting.id;
  }
});

// Navigation helper
const navigateToTab = (tab: string) => {
  currentTab.value = tab;
};

// Form handling
const meetingAgendaForm = useForm({
  meeting: props.meeting.id,
  agendaItems: [],
});

const selectedAgendaItem = ref<App.Entities.AgendaItem | null>(null);

// Computed values
const mainInstitution: App.Entities.Institution | string =
  props.meeting.institutions?.[0] ?? "Be institucijos";

const meetingTitle = computed(() => {
  if (props.meeting.title && props.meeting.title !== "") {
    return props.meeting.title;
  }

  const institutionName = typeof mainInstitution === 'string'
    ? mainInstitution
    : mainInstitution.name;

  return `${formatStaticTime(new Date(props.meeting.start_time), {
    year: "numeric",
    month: "long",
    day: "numeric",
  })} ${genitivizeEveryWord(institutionName)} posėdis`;
});

// Hero subtitle - institution name with link
const heroSubtitle = computed(() => {
  if (typeof mainInstitution === 'string') {
    return mainInstitution;
  }
  return mainInstitution.name;
});

// Badge for meeting type
const meetingBadge = computed(() => ({
  label: $t('Posėdis'),
  variant: 'secondary' as const,
  icon: Video
}));

// Format date for navigation buttons
const formatMeetingNavDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() => {
  if (typeof mainInstitution === 'string') {
    return [
      { label: mainInstitution, icon: Icons.INSTITUTION },
      { label: meetingTitle.value, icon: Icons.MEETING }
    ];
  }

  return BreadcrumbHelpers.adminShow(
    mainInstitution.name,
    "institutions.show",
    { institution: mainInstitution.id },
    meetingTitle.value,
    Icons.INSTITUTION,
    Icons.MEETING
  );
});

// Event handlers
const handleMeetingFormSubmit = (meeting: App.Entities.Meeting) => {
  router.patch(route("meetings.update", props.meeting.id), meeting, {
    onSuccess: () => {
      showMeetingModal.value = false;
    },
  });
};

const handleAgendaClick = (agendaItem: App.Entities.AgendaItem) => {
  selectedAgendaItem.value = agendaItem;
  showAgendaItemUpdateModal.value = true;
};

const handleAgendaItemDelete = (agendaItem: App.Entities.AgendaItem) => {
  router.delete(route("agendaItems.destroy", agendaItem.id));
};

const handleAgendaItemUpdate = (formValues: Record<string, any>) => {
  if (!selectedAgendaItem.value) return;
  
  router.patch(route("agendaItems.update", selectedAgendaItem.value.id), formValues, {
    onSuccess: () => {
      showAgendaItemUpdateModal.value = false;
    },
  });
};

const handleSingleAgendaItemSubmit = (data: { meeting_id: string; title: string; description?: string; brought_by_students?: boolean }) => {
  loading.value = true;

  router.post(route("agendaItems.store"), {
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
    .transform((data) => ({
      meeting_id: props.meeting.id,
      ...agendaItems,
    }))
    .post(route("agendaItems.store"), {
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

  router.delete(route("meetings.destroy", props.meeting.id), {
    data: { redirect_to: redirectTo },
    onSuccess: () => {
      showDeleteDialog.value = false;
    }
  });
};
</script>
