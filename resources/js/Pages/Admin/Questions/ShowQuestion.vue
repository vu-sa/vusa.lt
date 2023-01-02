<template>
  <PageContent :title="question.title" breadcrumb>
    <template #above-header>
      <NBreadcrumb class="mb-4 w-full">
        <NBreadcrumbItem @click="Inertia.get(route('dashboard'))">
          <div>
            <NIcon class="mr-2" size="16" :component="Home24Filled"> </NIcon>

            Pradinis
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.get(route('dutyInstitutions.show', question.institution.id))
          "
          ><div>
            <NIcon class="mr-2" size="16" :component="PeopleTeam24Filled">
            </NIcon>
            <NEllipsis style="max-width: 200px">
              {{ question.institution.name }}</NEllipsis
            >
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem>
          <div>
            <NIcon
              class="mr-2"
              size="16"
              :component="BookQuestionMark20Filled"
            />
            {{ question.title }}
          </div>
        </NBreadcrumbItem>
      </NBreadcrumb>
    </template>
    <!-- <template #after-heading>
        <StatusTag :status="question.status" />
      </template> -->
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ShowActivityLog :activities="question.activities" />
        <MoreOptionsButton
          disabled
          edit
          @edit-click="showQuestionModal = true"
        ></MoreOptionsButton>
      </div>
    </template>
    <QuestionGroupCard
      :question="question"
      :question-group="question.question_group"
    />
    <NTabs
      animated
      type="line"
      :default-value="currentQuestionsTabPane"
      @update:value="updateQuestionsTabPane"
    >
      <NTabPane name="Apie">
        <p>{{ question.description }}</p>
      </NTabPane>
      <NTabPane name="Veiklos">
        <template #tab>
          <div class="flex gap-2">
            Veiklos
            <NTag size="small" round>
              {{ question.doings.length }}
            </NTag>
          </div>
        </template>

        <DoingsTabPane
          :question="question"
          :doings="question.doings"
          :doing-template="doingTemplate"
          :doing-types="doingTypes"
        ></DoingsTabPane>
      </NTabPane>
    </NTabs>
  </PageContent>
  <CardModal
    v-model:show="showQuestionModal"
    title="Redaguoti klausimą"
    @close="showQuestionModal = false"
    ><QuestionForm
      :form="question"
      :duty-institution="question.institution"
      @question-stored="showQuestionModal = false"
    ></QuestionForm
  ></CardModal>
</template>

<script setup lang="tsx">
import {
  BookQuestionMark20Filled,
  Home24Filled,
  PeopleTeam24Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NEllipsis,
  NIcon,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { useStorage } from "@vueuse/core";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingsTabPane from "@/Components/TabPaneContent/DoingsTabPane.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QuestionForm from "@/Components/AdminForms/QuestionForm.vue";
import QuestionGroupCard from "@/Components/Cards/QuickContentCards/QuestionGroupCard.vue";
import ShowActivityLog from "@/Components/Buttons/ShowActivityLog.vue";

defineOptions({ layout: AdminLayout });

defineProps<{
  question: Record<string, any>;
  doingTypes: Record<string, any>;
}>();

const showQuestionModal = ref(false);

const currentQuestionsTabPane = useStorage(
  "admin-CurrentQuestionsTabPane",
  "Apie"
);

const updateQuestionsTabPane = (value) => {
  currentQuestionsTabPane.value = value;
};

const doingTemplate = {
  title: "",
  type_id: "",
  status: "Sukurtas",
  // datetime now YYYY-MM-DD HH:MM:SS and delimit T
  date: new Date().toISOString().split("T").join(" ").slice(0, 16) + ":00",
};
</script>
