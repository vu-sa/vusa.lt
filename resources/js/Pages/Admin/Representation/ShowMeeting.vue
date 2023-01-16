<template>
  <ShowPageLayout
    :model="meeting"
    :breadcrumb-options="breadcrumbOptions"
    :title="meetingTitle"
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
    <template #below-header>
      <div class="flex gap-2">
        <NTag v-for="type in meeting.types" :key="type.id" size="small">{{
          type.title
        }}</NTag>
      </div>
    </template>
    <div>
      <h3>Darbotvarkė</h3>
      <ol class="list-inside">
        <li
          v-for="(agenda_item, index) in meeting.agenda_items"
          :key="agenda_item.id"
          class="flex group gap-2"
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
    <NDivider />
    <div class="flex mt-4 items-center gap-4">
      <h2 class="mb-0">Dokumentai</h2>
      <NMessageProvider>
        <FileUploaderBasicButton
          @click="showFileUploadModal = true"
        ></FileUploaderBasicButton>
        <FileUploader
          :show="showFileUploadModal"
          :sharepoint-file-type-options="sharepointFileTypeOptions"
          :content-model="contentModel"
          @close="showFileUploadModal = false"
        ></FileUploader>
      </NMessageProvider>
    </div>
    <div class="flex m-4 max-w-4xl flex-wrap gap-6">
      <FileButton
        v-for="document in sharepointFiles"
        :key="document.id"
        :document="document"
        @click="selectedDocument = document"
      ></FileButton>
      <NewGridItemButton :icon="Icons.SHAREPOINT_DOCUMENT"
        >Įkelti naują dokumentą?</NewGridItemButton
      >
    </div>
  </ShowPageLayout>
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
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
</template>

<script setup lang="tsx">
import {
  type DropdownOption,
  NButton,
  NDivider,
  NIcon,
  NMessageProvider,
  NTag,
} from "naive-ui";
import { Edit24Filled, PeopleTeam24Filled } from "@vicons/fluent";
import { computed, ref } from "vue";

import { documentTemplate, modelTypes } from "@/Types/formOptions";
import { formatStaticTime } from "@/Utils/IntlTime";
import { router } from "@inertiajs/vue3";
import AgendaItemForm from "@/Components/AdminForms/AgendaItemForm.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FileButton from "@/Features/Admin/SharepointFileManager/FileButton.vue";
import FileSelectDrawer from "@/Features/Admin/SharepointFileManager/FileDrawer.vue";
import FileUploader from "@/Features/Admin/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Features/Admin/SharepointFileManager/FileUploaderBasicButton.vue";
import Icons from "@/Types/Icons/regular";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import NewGridItemButton from "@/Components/Buttons/NewGridItemButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  // TODO: need to define this type
  sharepointFiles: App.Entities.SharepointDocument[];
}>();

const showMeetingModal = ref(false);
const showAgendaItemModal = ref(false);
const showFileUploadModal = ref(false);

const selectedDocument = ref<App.Entities.SharepointDocument | null>(null);
const selectedAgendaItem = ref<App.Entities.AgendaItem | null>(null);

const mainInstitution: App.Entities.Institution | string =
  props.meeting.institutions?.[0] ?? "Be institucijos";

const meetingTitle = `${formatStaticTime(new Date(props.meeting.start_time), {
  year: "numeric",
  month: "long",
  day: "2-digit",
})} ${mainInstitution.name} posėdis`;

const contentModel = computed(() => ({
  id: props.meeting.id,
  title: props.meeting.title,
  type: "App\\Models\\Meeting",
  modelTypes: props.meeting.types,
}));

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
    onClick: () => {
      showModal.value = true;
    },
  },
];

const handleAgendaItemSubmit = (agendaItem: App.Entities.AgendaItem) => {
  router.patch(route("agendaItems.update", agendaItem.id), agendaItem, {
    onSuccess: () => {
      showAgendaItemModal.value = false;
    },
  });
};
</script>
