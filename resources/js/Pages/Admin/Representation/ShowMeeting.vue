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
        :more-options="additionalDropdownOptions"
        @edit-click="showModal = true"
      ></MoreOptionsButton>
      <CardModal
        v-model:show="showMeetingModal"
        @close="showMeetingModal = false"
      >
        <MeetingForm
          :meeting="meeting"
          :model-route="'meetings.update'"
          @success="showModal = false"
        ></MeetingForm>
      </CardModal>
    </template>
    <div>
      <h3>Darbotvarkė</h3>
      <ol class="list-inside">
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
    </div>
    <CardModal
      v-model:show="showAgendaItemModal"
      @close="showAgendaItemModal = false"
    >
      <AgendaItemForm
        v-if="selectedAgendaItem"
        :agenda-item="selectedAgendaItem"
        @submit="handleAgendaItemSubmit"
      />
    </CardModal>
    <template #below>
      <div v-if="currentTab === 'Failai'" class="grid grid-cols-ramFill gap-4">
        <FileButton
          v-for="document in sharepointFiles"
          :key="document.id"
          :document="document"
          @click="selectedDocument = document"
        ></FileButton>
        <NewGridItemButton :icon="Icons.SHAREPOINT_FILE"
          >Įkelti naują dokumentą?</NewGridItemButton
        >
      </div>
      <TaskManager
        v-else-if="currentTab === 'Užduotys'"
        :taskable="{ id: meeting.id, type: 'App\\Models\\Meeting' }"
        :tasks="meeting.tasks"
      />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { type DropdownOption, NButton, NDivider, NIcon, NTag } from "naive-ui";
import { Edit24Filled, PeopleTeam24Filled } from "@vicons/fluent";
import { computed, ref } from "vue";

import { formatStaticTime } from "@/Utils/IntlTime";
import { genitivizeEveryWord } from "@/Utils/String";
import { modelTypes } from "@/Types/formOptions";
import { router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import AgendaItemForm from "@/Components/AdminForms/AgendaItemForm.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FileButton from "@/Features/Admin/SharepointFileManager/Viewer/FileButton.vue";
import Icons from "@/Types/Icons/filled";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import NewGridItemButton from "@/Components/Buttons/NewGridItemButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  // TODO: need to define this type
  sharepointFiles: App.Entities.SharepointFile[];
}>();

const showMeetingModal = ref(false);
const showAgendaItemModal = ref(false);
const currentTab = useStorage("show-meeting-tab", "Failai");

const selectedDocument = ref<App.Entities.SharepointFile | null>(null);
const selectedAgendaItem = ref<App.Entities.AgendaItem | null>(null);

const mainInstitution: App.Entities.Institution | string =
  props.meeting.institutions?.[0] ?? "Be institucijos";

const meetingTitle = `${formatStaticTime(new Date(props.meeting.start_time), {
  year: "numeric",
  month: "long",
  day: "numeric",
})} ${genitivizeEveryWord(mainInstitution.name)} posėdis`;

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
    icon: PeopleTeam24Filled,
    routeOptions: {
      name: "institutions.show",
      params: {
        institution: mainInstitution.id,
      },
    },
  },
];

const additionalDropdownOptions: DropdownOption[] = [
  {
    label: "Pridėti darbotvarkės punktų",
    key: "add-agenda-item",
    icon: () => <NIcon component={Icons.AGENDA_ITEM} />,
    // onClick: () => {
    //   showModal.value = true;
    // },
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
    count: props.meeting.files.length,
  },
  {
    name: "Užduotys",
    icon: Icons.TASK,
    count: props.meeting.tasks?.length,
  },
];
</script>
