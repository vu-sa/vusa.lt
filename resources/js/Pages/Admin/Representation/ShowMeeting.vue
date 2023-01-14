<template>
  <PageContent :breadcrumb="true" :title="meetingTitle">
    <template #above-header>
      <AdminBreadcrumbDisplayer
        :options="breadcrumbOptions"
        class="mb-4 w-full"
      />
    </template>
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ActivityLogButton :activities="meeting.activities" />
        <MoreOptionsButton
          edit
          @edit-click="showModal = true"
        ></MoreOptionsButton>
      </div>
      <CardModal v-model:show="showModal" @close="showModal = false">
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
    <div class="m-4 flex items-center gap-4">
      <h2 class="mb-0">Dokumentai</h2>
      <NMessageProvider>
        <FileUploader
          :button="FileUploaderBasicButton"
          :sharepoint-file-type-options="sharepointFileTypeOptions"
          :content-model="contentModel"
        ></FileUploader>
      </NMessageProvider>
    </div>
    <div class="m-4 flex max-w-4xl flex-wrap gap-6">
      <FileButton
        v-for="document in sharepointFiles"
        :key="document.id"
        :document="document"
        @click="selectedDocument = document"
      ></FileButton>
    </div>
  </PageContent>
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
</template>

<script setup lang="tsx">
import { NMessageProvider, NTag } from "naive-ui";
import { PeopleTeam24Filled } from "@vicons/fluent";
import { computed, ref } from "vue";
import { useStorage } from "@vueuse/core";

import { documentTemplate, modelTypes } from "@/Types/formOptions";
import { formatStaticTime } from "@/Utils/IntlTime";
import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import AdminBreadcrumbDisplayer, {
  type BreadcrumbOption,
} from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FileButton from "@/Components/SharepointFileManager/FileButton.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/SharepointFileManager/FileUploaderBasicButton.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  // TODO: need to define this type
  sharepointFiles: App.Entities.SharepointDocument[];
}>();

const showModal = ref(false);
const selectedDocument = ref<App.Entities.SharepointDocument | null>(null);

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
</script>
