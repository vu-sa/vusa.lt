<template>
  <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950">
    <div class="container mx-auto px-4 py-6 space-y-8">
      <!-- Meeting Hero Section -->
      <MeetingHero :meeting :main-institution :agenda-items="meeting.agenda_items" @edit="showMeetingModal = true"
        @show-delete-dialog="showDeleteDialog = true" />

      <!-- Tabs Navigation -->
      <Tabs v-model="currentTab" class="space-y-6">
        <TabsList class="gap-2">
          <TabsTrigger value="agenda">
            {{ $t('Darbotvarkė') }}
            <span v-if="meeting.agenda_items?.length" class="ml-1.5 text-xs opacity-70">
              ({{ meeting.agenda_items.length }})
            </span>
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

        <!-- Agenda Tab -->
        <TabsContent value="agenda" class="space-y-8">
          <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Agenda Items (Main Content) -->
            <div class="xl:col-span-2">
              <SortableCardContainer :items="meeting.agenda_items" :meeting-id="meeting.id"
                @add="showSingleAgendaItemModal = true" @add-bulk="showAgendaItemStoreModal = true" @edit="handleAgendaClick" @delete="handleAgendaItemDelete" />
            </div>
          </div>
        </TabsContent>

        <!-- Files Tab -->
        <TabsContent value="files">
          <FileManager :starting-path="meeting.sharepointPath" :fileable="{ ...meeting, type: 'Meeting' }" />
        </TabsContent>

        <!-- Tasks Tab -->
        <TabsContent value="tasks">
          <TaskManager :taskable="{ id: meeting.id, type: 'App\\Models\\Meeting' }" :tasks="meeting.tasks" />
        </TabsContent>
      </Tabs>
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
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t("Pridėti darbotvarkės punktus") }}</DialogTitle>
        </DialogHeader>
        <AgendaItemsForm 
          class="w-full" 
          :loading 
          mode="add"
          :submit-label="$t('Pridėti punktus')"
          :show-skip-button="false"
          @submit="handleAgendaItemsFormSubmit" 
        />
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
  </div>
</template>

<script setup lang="tsx">
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";
import { AlertTriangle, Plus, Trash2 } from 'lucide-vue-next';

import { formatStaticTime } from "@/Utils/IntlTime";
import { genitivizeEveryWord } from "@/Utils/String";
import Icons from "@/Types/Icons/filled";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

// UI Components
import { Button } from "@/Components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";

// Custom Components
import MeetingHero from "@/Components/Meetings/MeetingHero.vue";
import SortableCardContainer from "@/Components/AgendaItems/SortableCardContainer.vue";
import SecondaryContentSection from "@/Components/Meetings/SecondaryContentSection.vue";
import AgendaItemForm from "@/Components/AdminForms/AgendaItemForm.vue";
import AddAgendaItemForm from "@/Components/AdminForms/AddAgendaItemForm.vue";
import AgendaItemsForm from "@/Components/AdminForms/Special/AgendaItemsForm.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";

// Icons

const props = defineProps<{
  meeting: App.Entities.Meeting;
}>();

// Component state
const showMeetingModal = ref(false);
const showAgendaItemStoreModal = ref(false);
const showSingleAgendaItemModal = ref(false);
const showAgendaItemUpdateModal = ref(false);
const showDeleteDialog = ref(false);
const currentTab = useStorage("show-meeting-tab", "agenda");
const loading = ref(false);

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

const handleSingleAgendaItemSubmit = (data: { meeting_id: string; title: string; description?: string }) => {
  loading.value = true;

  router.post(route("agendaItems.store"), {
    meeting_id: data.meeting_id,
    agendaItemTitles: [data.title],
    agendaItemDescriptions: data.description ? [data.description] : [],
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
