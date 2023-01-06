<template>
  <PageContent :title="doing.title" :breadcrumb="true">
    <template #above-header>
      <NBreadcrumb v-if="matter" class="mb-4 w-full">
        <NBreadcrumbItem>
          <NDropdown
            placement="bottom-start"
            :options="breadcrumbOptions"
            @select="handleBreadcrumbDropdownSelect"
          >
            <span>...</span>
          </NDropdown>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.visit(
              route('matters.show', {
                matter: matter.id,
              })
            )
          "
        >
          <div>
            <NIcon
              class="mr-2"
              size="16"
              :component="BookQuestionMark20Filled"
            />
            {{ matter.title }}
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          ><div>
            <NIcon class="mr-1" size="16" :component="Sparkle20Filled" />
            {{ doing.title }}
          </div></NBreadcrumbItem
        >
      </NBreadcrumb>
    </template>
    <template #after-heading>
      <StatusTag :status="doing.status" />
      <NTag v-if="!matter" size="small" round :bordered="false" type="warning"
        >Veikla be klausimo</NTag
      >

      <span class="text-gray-500">{{ doing.date }}</span>
    </template>
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ShowActivityLog :activities="doing.activities" />
        <MoreOptionsButton
          edit
          @edit-click="showModal = true"
        ></MoreOptionsButton>
      </div>
      <CardModal
        v-model:show="showModal"
        :title="`${$t('Redaguoti veiklą')} (${matter?.title})`"
        @close="showModal = false"
      >
        <DoingForm
          :doing="doing"
          :matter="matter"
          :model-route="'doings.update'"
          @success="showModal = false"
        ></DoingForm>
      </CardModal>
    </template>
    <template #below-header>
      <div class="flex gap-2">
        <NTag v-for="type in doing.types" :key="type.id" size="small">{{
          type.title
        }}</NTag>
      </div>
    </template>

    <NTabs
      :default-value="currentDoingsTabPane"
      animated
      type="line"
      @update:value="updateDoingsTabPane"
    >
      <NTabPane name="Apie">
        <div v-if="doing.tasks.length > 0" class="m-4 h-fit">
          <h2>Užduotys</h2>
          <SingleTask v-for="task in doing.tasks" :key="task.id" :task="task" />
        </div>
        <div
          v-if="doing.extra_attributes?.andOther"
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
            v-for="document in documents"
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
            <CommentViewer :comments="doing.comments" />
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
  BookQuestionMark20Filled,
  DocumentEdit24Regular,
  Home24Filled,
  PeopleTeam24Filled,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NCard,
  NDropdown,
  NIcon,
  NMessageProvider,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { computed, ref } from "vue";


import { contentTypeOptions, documentTemplate } from "@/Composables/someTypes";
import { useStorage } from "@vueuse/core";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import CommentTipTap from "@/Components/TipTap/CommentTipTap.vue";
import CommentViewer from "@/Components/Comments/CommentViewer.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import FileButton from "@/Components/SharepointFileManager/FileButton.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/SharepointFileManager/FileUploaderBasicButton.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ShowActivityLog from "@/Components/Buttons/ShowActivityLog.vue";
import SingleTask from "@/Components/Tasks/SingleTask.vue";
import StatusTag from "@/Components/Tags/StatusTag.vue";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps<{
  doing: Record<string, any>;
  matter: Record<string, any> | null;
  documents: Record<string, any>[];
}>();

const currentCommentField = ref("");
const showModal = ref(false);
const selectedDocument = ref(null);

const currentDoingsTabPane = useStorage("admin-CurrentDoingsTabPane", "Apie");

const updateDoingsTabPane = (value) => {
  currentDoingsTabPane.value = value;
};

const contentModel = computed(() => ({
  id: props.doing.id,
  title: props.doing.title,
  type: "App\\Models\\Doing",
  modelTypes: props.doing.types,
}));

const breadcrumbOptions = [
  {
    label: "Pradinis",
    key: "dashboard",
    icon: () => {
      return <NIcon component={Home24Filled}></NIcon>;
    },
  },
  {
    label: props.matter?.institution.name,
    key: "institution",
    icon: () => {
      return <NIcon component={PeopleTeam24Filled}></NIcon>;
    },
  },
];

const handleBreadcrumbDropdownSelect = (key) => {
  switch (key) {
    case "dashboard":
      Inertia.visit(route("dashboard"));
      break;
    case "institution":
      Inertia.visit(route("institutions.show", props.matter.institution.id));
      break;
    default:
      break;
  }
};
</script>
