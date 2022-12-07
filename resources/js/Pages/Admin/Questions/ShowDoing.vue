<template>
  <PageContent :title="doing.title">
    <template #after-heading>
      <StatusTag :status="doing.status" />
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
          :title="`${$t('Redaguoti veiklą')} (${question.title})`"
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
    <template #below-header>
      <NBreadcrumb class="mb-4 w-full">
        <NBreadcrumbItem
          @click="
            Inertia.get(route('dutyInstitutions.show', question.institution.id))
          "
        >
          <div>
            <NIcon
              class="mr-2"
              size="16"
              :component="PeopleTeam32Filled"
            ></NIcon
            >{{ question.institution.name }}
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.visit(
              route('dutyInstitutions.questions.show', {
                question: question.id,
                dutyInstitution: question.institution.id,
              })
            )
          "
        >
          <div>
            <NIcon
              class="mr-2"
              size="16"
              :component="BookQuestionMark20Filled"
            />{{ question.title }}
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          ><div>
            <NIcon class="mr-2" size="16" :component="Sparkle20Filled" />{{
              doing.title
            }}
          </div></NBreadcrumbItem
        >
      </NBreadcrumb>
    </template>

    <NTabs default-value="Dokumentai" animated type="card">
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
              :show-object-name="false"
              :content-type-options="contentTypeOptions"
              :content-model="contentModel"
              :institution="question.institution"
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
              v-model:text="comment"
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
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

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
} from "naive-ui";
import { computed, ref } from "vue";
import route from "ziggy-js";

import { contentTypeOptions, documentTemplate } from "@/Composables/someTypes";
import CommentTipTap from "@/Components/CommentTipTap.vue";
import CommentViewer from "@/Components/Admin/Comments/CommentViewer.vue";
import DoingForm from "@/Components/Admin/Forms/DoingForm.vue";
import FileButton from "@/Components/Admin/Buttons/FileButton.vue";
import FileSelectDrawer from "@/Components/Admin/Nav/FileSelectDrawer.vue";
import FileUploader from "@/Components/Admin/Buttons/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/Admin/Buttons/FileUploaderBasicButton.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import ShowActivityLog from "@/Components/Admin/Buttons/ShowActivityLog.vue";
import StatusTag from "@/Components/Admin/StatusTag.vue";
import TaskViewer from "@/Components/Admin/Tasks/TaskViewer.vue";

const props = defineProps<{
  doing: Record<string, any>;
  question: Record<string, any>;
  documents: Record<string, any>[];
}>();

const comment = ref("");

const showModal = ref(false);

const selectedDocument = ref(null);

const contentModel = computed(() => ({
  id: props.doing.id,
  title: props.doing.title,
  type: "App\\Models\\Doing",
  // contentTypes: props.doing.types,
}));
</script>
