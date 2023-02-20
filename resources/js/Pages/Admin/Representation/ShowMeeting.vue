<template>
  <ShowPageLayout
    :model="meeting"
    :breadcrumb-options="breadcrumbOptions"
    :title="meetingTitle"
    :related-models="relatedModels"
    :current-tab="currentTab"
    @change:tab="currentTab = $event"
  >
    <template #more-options>
      <MoreOptionsButton
        edit
        @edit-click="showMeetingModal = true"
      ></MoreOptionsButton>
      <CardModal
        v-model:show="showMeetingModal"
        title="Redaguoti posėdžio datą"
        @close="showMeetingModal = false"
      >
        <MeetingForm
          class="mt-2"
          :meeting="meeting"
          @submit="handleMeetingFormSubmit"
        ></MeetingForm>
      </CardModal>
    </template>
    <NCard
      class="max-w-sm rounded-sm border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
      size="small"
      :segmented="{ footer: 'soft' }"
    >
      <NDivider style="margin-top: 1rem">Darbotvarkė</NDivider>
      <ol class="pl-4">
        <li
          v-for="(agenda_item, index) in meeting.agenda_items"
          :key="agenda_item.id"
          class="group flex gap-2"
        >
          <span>{{ index + 1 }}. {{ agenda_item.title }}</span>
          <NButton
            size="tiny"
            class="invisible transition duration-200 group-hover:visible"
            strong
            text
            @click="handleAgendaClick(agenda_item)"
            ><template #icon><NIcon :component="Edit24Filled"></NIcon></template
          ></NButton>
        </li>
      </ol>
      <template #footer>
        <NButton size="small"
          >Pridėti<template #icon
            ><NIcon size="16" :component="Icons.AGENDA_ITEM" /></template
        ></NButton>
      </template>
    </NCard>
    <CardModal
      v-model:show="showAgendaItemModal"
      title="Redaguoti darbotvarkės punktą"
      :segmented="{ content: 'soft' }"
      @close="showAgendaItemModal = false"
    >
      <AgendaItemForm
        v-if="selectedAgendaItem"
        :agenda-item="selectedAgendaItem"
        @submit="handleAgendaItemSubmit"
      />
    </CardModal>
    <template #below>
      <FileManager
        v-if="currentTab === 'Failai'"
        :starting-path="meeting.sharepointPath"
        :fileable="{ ...meeting, type: 'Meeting' }"
      ></FileManager>
      <TaskManager
        v-else-if="currentTab === 'Užduotys'"
        :taskable="{ id: meeting.id, type: 'App\\Models\\Meeting' }"
        :tasks="meeting.tasks"
      />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { Edit24Filled } from "@vicons/fluent";
import { NButton, NCard, NDivider, NIcon } from "naive-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import { formatStaticTime } from "@/Utils/IntlTime";
import { genitivizeEveryWord } from "@/Utils/String";
import { modelTypes } from "@/Types/formOptions";
import AgendaItemForm from "@/Components/AdminForms/AgendaItemForm.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import Icons from "@/Types/Icons/filled";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  meeting: App.Entities.Meeting;
}>();

const showMeetingModal = ref(false);
const showAgendaItemModal = ref(false);
const currentTab = useStorage("show-meeting-tab", "Failai");

const selectedAgendaItem = ref<App.Entities.AgendaItem | null>(null);

const handleMeetingFormSubmit = (meeting: App.Entities.Meeting) => {
  router.patch(route("meetings.update", meeting.id), meeting, {
    preserveState: true,
    onSuccess: () => {
      showMeetingModal.value = false;
    },
  });
};

const mainInstitution: App.Entities.Institution | string =
  props.meeting.institutions?.[0] ?? "Be institucijos";

const meetingTitle =
  props.meeting.title === ""
    ? `${formatStaticTime(new Date(props.meeting.start_time), {
        year: "numeric",
        month: "long",
        day: "numeric",
      })} ${genitivizeEveryWord(mainInstitution.name)} posėdis`
    : props.meeting.title;

const sharepointFileTypeOptions = computed(() => {
  return modelTypes.sharepointFile.map((type) => ({
    label: type,
    value: type,
  }));
});

const handleAgendaClick = (agendaItem: App.Entities.AgendaItem) => {
  selectedAgendaItem.value = agendaItem;
  showAgendaItemModal.value = true;
};

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: mainInstitution.name,
    icon: Icons.INSTITUTION,
    routeOptions: {
      name: "institutions.show",
      params: {
        institution: mainInstitution.id,
      },
    },
  },
  {
    label: meetingTitle,
    icon: Icons.MEETING,
  },
];

const handleAgendaItemSubmit = (agendaItem: App.Entities.AgendaItem) => {
  router.patch(route("agendaItems.update", agendaItem.id), agendaItem, {
    onSuccess: () => {
      showAgendaItemModal.value = false;
    },
  });
};

const relatedModels = [
  {
    name: "Failai",
    icon: Icons.SHAREPOINT_FILE,
  },
  {
    name: "Užduotys",
    icon: Icons.TASK,
    count: props.meeting.tasks?.length,
  },
];
</script>
