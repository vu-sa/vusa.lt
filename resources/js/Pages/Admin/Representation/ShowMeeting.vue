<template>
  <PageContent :title="meeting.title" :breadcrumb="true">
    <template #above-header>
      <AdminBreadcrumbDisplayer
        :options="breadcrumbOptions"
        class="mb-4 w-full"
      />
    </template>
    <template #after-heading>
      <!-- <StatusTag :status="meeting.status" /> -->
      <!-- <NTag
        v-if="!meeting.matters"
        size="small"
        round
        :bordered="false"
        type="warning"
        >Veikla be klausimų</NTag
      > -->

      <span class="text-gray-500">{{
        formatStaticTime(meeting.start_time * 1000)
      }}</span>
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

    <NTabs
      :default-value="currentMeetingsTabPane"
      animated
      type="line"
      @update:value="updateMeetingsTabPane"
    >
      <NTabPane name="Apie">
        <div v-if="meeting.tasks.length > 0" class="m-4 h-fit">
          <h2>Užduotys</h2>
          <SingleTask
            v-for="task in meeting.tasks"
            :key="task.id"
            :task="task"
          />
        </div>
        <div
          v-if="meeting.extra_attributes?.andOther"
          class="border border-vusa-red p-4"
        >
          Įvykį sukūręs žmogus pažymėjo jį, kaip turintį kitų klausimų...
          <!-- Sutvarkyti šią funkciją... -->
        </div>
      </NTabPane>
      <NTabPane name="Dokumentai">
        <div class="m-4 flex items-center gap-4">
          <h2 class="mb-0">Dokumentai</h2>
          <NMessageProvider>
            <FileUploader
              :button="FileUploaderBasicButton"
              :content-type-options="contentTypeOptions"
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
      </NTabPane>
      <NTabPane name="Komentarai">
        <div class="max-w-2xl">
          <NCard class="subtle-gray-gradient">
            <h2>Komentarai</h2>
            <CommentTipTap
              v-model:text="currentCommentField"
              :content-model="contentModel"
            />
            <CommentViewer :comments="meeting.comments" />
          </NCard>
        </div>
      </NTabPane>
    </NTabs>
  </PageContent>
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  type DropdownOption,
  NCard,
  NIcon,
  NMessageProvider,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { PeopleTeam24Filled } from "@vicons/fluent";
import { computed, ref } from "vue";
import { useStorage } from "@vueuse/core";
import Icons from "@/Types/Icons/regular";

import { contentTypeOptions, documentTemplate } from "@/Composables/someTypes";
import { formatStaticTime } from "@/Utils/IntlTime";
import AdminBreadcrumbDisplayer, {
  type BreadcrumbOption,
} from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import CommentTipTap from "@/Components/TipTap/CommentTipTap.vue";
import CommentViewer from "@/Components/Comments/CommentViewer.vue";
import FileButton from "@/Components/SharepointFileManager/FileButton.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/SharepointFileManager/FileUploaderBasicButton.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import SingleTask from "@/Components/Tasks/SingleTask.vue";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  // TODO: need to define this type
  sharepointFiles: App.Entities.SharepointDocument[];
}>();

const currentCommentField = ref("");
const showModal = ref(false);
const selectedDocument = ref(null);

const currentMeetingsTabPane = useStorage(
  "admin-CurrentMeetingsTabPane",
  "Apie"
);

const mainInstitution = computed(() => {
  return props.meeting.institutions?.[0] ?? "Be institucijos";
});

const updateMeetingsTabPane = (value) => {
  currentMeetingsTabPane.value = value;
};

const contentModel = computed(() => ({
  id: props.meeting.id,
  title: props.meeting.title,
  type: "App\\Models\\Meeting",
  modelTypes: props.meeting.types,
}));

// const breadcrumbDropdownOptions: DropdownOption[] = [
//   {
//     label: () => (
//       <Link
//         href={route("institutions.show", props.meeting.institutions?.[0].id)}
//       >
//         {props.meeting.institutions?.[0].name}
//       </Link>
//     ),
//     icon: () => {
//       return <NIcon component={PeopleTeam24Filled}></NIcon>;
//     },
//   },
// ];

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: mainInstitution.value.name,
    icon: PeopleTeam24Filled,
    routeOptions: {
      name: "institutions.show",
      params: {
        institution: mainInstitution.value.id,
      },
    },
  },
  // {
  //   label: props.meeting.matters?.[0].title,
  //   icon: Icons.MATTER,
  //   routeOptions: {
  //     name: "matters.show",
  //     params: {
  //       matter: props.meeting.matters?.[0].id,
  //     },
  //   },
  // },
  // {
  //   label: props.meeting.start_time,
  //   icon: Icons.MEETING,
  // },
];
</script>