<template>
  <PageContent :title="doing.title">
    <template #after-heading><StatusTag :status="doing.status" /> </template>
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
          :title="`${$t('Sukurti veiklą')} (${question.title})`"
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
      <NBreadcrumb class="w-full">
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
          ><NPopover class="max-w-xl" placement="right"
            ><template #trigger
              ><div>
                <NIcon
                  class="mr-2"
                  size="16"
                  :component="BookQuestionMark20Filled"
                />{{ question.title }}
              </div></template
            >{{ question.description }}</NPopover
          ></NBreadcrumbItem
        >
        <NBreadcrumbItem
          ><div>
            <NIcon class="mr-2" size="16" :component="Sparkle20Filled" />{{
              doing.title
            }}
          </div></NBreadcrumbItem
        >
      </NBreadcrumb>
    </template>
    <template #aside-card>
      <div class="w-96">
        <div v-if="doing.tasks.length > 0" class="main-card h-fit">
          <h2>Užduotys</h2>
          <TaskViewer :tasks="doing.tasks" />
        </div>
        <div class="main-card">
          <h2>Komentarai</h2>
          <CommentViewer :comments="doing.comments" />
        </div>
        <CommentTipTap
          v-model:text="comment"
          class="main-card"
          :content-model="contentModel"
        />
      </div>
    </template>
    <div class="grid grid-cols-2">
      <div class="main-card col-span-full">
        <div class="mb-4 flex items-center gap-4">
          <h2 class="mb-0">Dokumentai</h2>
          <FileUploader
            :content-type-options="contentTypeOptions"
            :content-model="contentModel"
          ></FileUploader>
        </div>
        <NDataTable :columns="columns" :data="documents"></NDataTable>
      </div>
    </div>
  </PageContent>
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
  NDataTable,
  NIcon,
  NModal,
  NPopover,
  NSpace,
  NTag,
} from "naive-ui";
import { computed, h, ref } from "vue";
import route from "ziggy-js";

import CommentTipTap from "@/Components/CommentTipTap.vue";
import CommentViewer from "@/Components/Admin/Comments/CommentViewer.vue";
import DoingForm from "@/Components/Admin/Forms/DoingForm.vue";
import FileUploader from "@/Components/Admin/Buttons/FileUploader.vue";
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

const contentModel = computed(() => ({
  id: props.doing.id,
  type: "App\\Models\\Doing",
}));

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    render(row) {
      return h("a", { href: row.webUrl, target: "_blank" }, row.name);
    },
  },
  {
    title: "Tipas",
    key: "type",
  },
  {
    title: "Raktažodžiai",
    key: "keywords",
    render(row) {
      return h(
        NSpace,
        {},
        {
          default: () =>
            row.keywords?.map((keyword) =>
              h(NTag, {}, { default: () => keyword })
            ),
        }
      );
    },
  },
  {
    title: "Data",
    key: "date",
  },
];

const contentTypeOptions = [
  {
    label: "Protokolai",
    value: "Protokolai",
  },
  {
    label: "Pristatymai",
    value: "Pristatymai",
  },
];

const computedStatusType = computed(() => {
  switch (props.doing.status) {
    case "Sukurtas":
      return "info";
    case "Atnaujintas":
      return "warning";
    case "Pabaigtas":
      return "success";
    case "Atmestas":
      return "error";
    default:
      return "info";
  }
});
</script>
