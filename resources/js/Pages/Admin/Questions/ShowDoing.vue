<template>
  <PageContent :title="doing.title" :breadcrumb="true">
    <template #above-header>
      <NBreadcrumb v-if="question" class="mb-4 w-full">
        <NBreadcrumbItem @click="Inertia.get(route('dashboard'))">
          <div>Pradinis</div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.get(route('dutyInstitutions.show', question.institution.id))
          "
        >
          <div>
            <!-- <NIcon
              class="mr-2"
              size="16"
              :component="PeopleTeam32Filled"
            ></NIcon> -->
            {{ question.institution.name }}
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.visit(
              route('questions.show', {
                question: question.id,
              })
            )
          "
        >
          <div>
            <!-- <NIcon
              class="mr-2"
              size="16"
              :component="BookQuestionMark20Filled"
            /> -->
            {{ question.title }}
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          ><div>
            <!-- <NIcon class="mr-2" size="16" :component="Sparkle20Filled" /> -->
            {{ doing.title }}
          </div></NBreadcrumbItem
        >
      </NBreadcrumb>
    </template>
    <template #after-heading>
      <StatusTag :status="doing.status" />
      <NTag v-if="!question" size="small" round :bordered="false" type="warning"
        >Veikla be klausimo</NTag
      >

      <span class="text-gray-500">{{ doing.date }}</span>
    </template>
    <template #aside-header>
      <div class="flex items-center gap-4">
        <NButton secondary circle @click="showModal = true"
          ><template #icon
            ><NIcon :component="DocumentEdit24Regular"></NIcon></template
        ></NButton>

        <ShowActivityLog :activities="doing.activities" />
        <NModal
          v-model:show="showModal"
          class="prose prose-sm max-w-xl dark:prose-invert"
          :title="`${$t('Redaguoti veiklą')} (${question?.title})`"
          :bordered="false"
          size="large"
          role="card"
          aria-modal="true"
          preset="card"
        >
          <DoingForm
            :doing="doing"
            :question="question"
            :model-route="'doings.update'"
            @success="showModal = false"
          ></DoingForm>
        </NModal>
      </div>
    </template>

    <NTabs default-value="Dokumentai" animated type="line">
      <NTabPane name="Aprašymas">
        <div v-if="doing.tasks.length > 0" class="m-4 h-fit">
          <h2>Užduotys</h2>
          <TaskViewer :tasks="doing.tasks" />
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
              :related-object-name="question.institution.name"
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
          <div class="main-card">
            <h2>Komentarai</h2>
            <CommentTipTap
              v-model:text="currentCommentField"
              :content-model="contentModel"
            />
            <CommentViewer :comments="doing.comments" />
          </div>
        </div>
      </NTabPane>
    </NTabs>
  </PageContent>
  <FileSelectDrawer
    :document="selectedDocument"
    @close-drawer="selectedDocument = documentTemplate"
  ></FileSelectDrawer>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  BookQuestionMark20Filled,
  DocumentEdit24Regular,
  PeopleTeam32Filled,
  Sparkle20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NIcon,
  NMessageProvider,
  NModal,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { computed, ref } from "vue";
import route from "ziggy-js";

import { contentTypeOptions, documentTemplate } from "@/Composables/someTypes";
import CommentTipTap from "@/Components/TipTap/CommentTipTap.vue";
import CommentViewer from "@/Components/Comments/CommentViewer.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import FileButton from "@/Components/SharepointFileManager/FileButton.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/SharepointFileManager/FileUploaderBasicButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ShowActivityLog from "@/Components/Buttons/ShowActivityLog.vue";
import StatusTag from "@/Components/Tags/StatusTag.vue";
import TaskViewer from "@/Components/Tasks/TaskViewer.vue";

const props = defineProps<{
  doing: Record<string, any>;
  question: Record<string, any> | null;
  documents: Record<string, any>[];
}>();

const currentCommentField = ref("");

const showModal = ref(false);

const selectedDocument = ref(null);

const contentModel = computed(() => ({
  id: props.doing.id,
  title: props.doing.title,
  type: "App\\Models\\Doing",
  modelTypes: props.doing.types,
}));
</script>
