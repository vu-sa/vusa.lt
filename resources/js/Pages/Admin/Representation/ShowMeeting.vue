<template>
  <AdminContentPage>
    <InertiaHead :title="meetingTitle" />

    <!-- Meeting Hero Section -->
    <MeetingHero 
      :meeting 
      :main-institution 
      :agenda-items="meeting.agenda_items ?? []" 
      :representatives 
      @edit="showMeetingModal = true"
      @show-delete-dialog="showDeleteDialog = true" 
    >
      <template #actions>
        <ActivityLogButton :activities="meeting.activities ?? []" />
      </template>
    </MeetingHero>

    <!-- Tabs Navigation -->
    <Tabs v-model="currentTab" class="space-y-6 mt-6">
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
      <TabsContent value="overview">
        <MeetingOverview 
          :meeting 
          :representatives 
          :activities="meeting.activities"
          @go-to-agenda="navigateToTab('agenda')"
          @go-to-files="navigateToTab('files')"
          @go-to-tasks="navigateToTab('tasks')"
          @edit="showMeetingModal = true"
        />
      </TabsContent>

      <!-- Agenda Tab -->
      <TabsContent value="agenda" class="space-y-8">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
          <!-- Agenda Items (Main Content) -->
          <div class="xl:col-span-2">
            <SortableCardContainer 
              :items="meeting.agenda_items ?? []" 
              :meeting-id="meeting.id"
              @add="showSingleAgendaItemModal = true" 
              @add-bulk="showAgendaItemStoreModal = true" 
              @edit="handleAgendaClick" 
              @delete="handleAgendaItemDelete" 
            />
          </div>
          <!-- Sidebar -->
          <div class="xl:col-span-1">
            <MeetingAgendaSidebar 
              :tasks="meeting.tasks"
              :has-protocol="meeting.has_protocol"
              :has-report="meeting.has_report"
              :previous-meeting
              :next-meeting
              @go-to-files="navigateToTab('files')"
              @go-to-tasks="navigateToTab('tasks')"
            />
          </div>
        </div>
      </TabsContent>

      <!-- Files Tab -->
      <TabsContent value="files">
        <FileManager :starting-path="meeting.sharepointPath" :fileable="{ id: meeting.id, type: 'Meeting' }" />
      </TabsContent>

      <!-- Tasks Tab -->
      <TabsContent value="tasks">
        <TaskManager :taskable="{ id: meeting.id, type: 'App\\Models\\Meeting' }" :tasks="meeting.tasks" />
      </TabsContent>
    </Tabs>

    <!-- Previous/Next Meeting Navigation (shown only on non-agenda tabs) -->
    <div v-if="(previousMeeting || nextMeeting) && currentTab !== 'agenda'" class="flex flex-col sm:flex-row justify-between gap-4 pt-8">
      <Link
        v-if="previousMeeting"
        :href="route('meetings.show', previousMeeting.id)"
        class="flex items-center gap-2 px-4 py-3 rounded-xl ring-1 ring-zinc-300 dark:ring-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors group"
      >
        <ChevronLeft class="h-5 w-5 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors" />
        <div class="text-left">
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Ankstesnis posėdis') }}</span>
          <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ formatMeetingNavDate(previousMeeting.start_time) }}</p>
        </div>
      </Link>
      <div v-else />

      <Link
        v-if="nextMeeting"
        :href="route('meetings.show', nextMeeting.id)"
        class="flex items-center gap-2 px-4 py-3 rounded-xl ring-1 ring-zinc-300 dark:ring-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors group sm:ml-auto"
      >
        <div class="text-right">
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Kitas posėdis') }}</span>
          <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ formatMeetingNavDate(nextMeeting.start_time) }}</p>
        </div>
        <ChevronRight class="h-5 w-5 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors" />
      </Link>
    </div>

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
import { AlertTriangle, Plus, Trash2, ChevronLeft, ChevronRight } from 'lucide-vue-next';

import { formatStaticTime } from "@/Utils/IntlTime";
import { genitivizeEveryWord } from "@/Utils/String";
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

// Layout
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";

// UI Components
import { Button } from "@/Components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";

// Custom Components
import MeetingHero from "@/Components/Meetings/MeetingHero.vue";
import MeetingOverview from "@/Components/Meetings/MeetingOverview.vue";
import MeetingAgendaSidebar from "@/Components/Meetings/MeetingAgendaSidebar.vue";
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
