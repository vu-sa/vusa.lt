<template>
  <QuickActionButton :icon="PeopleTeamAdd24Filled" @click="showDoingForm = true"
    >Pranešti apie artėjantį posėdį</QuickActionButton
  >
  <NModal
    v-model:show="showDoingForm"
    display-directive="show"
    class="prose prose-sm max-w-xl transition dark:prose-invert"
    :title="`${$t('Pranešti apie artėjantį posėdį')}`"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
    :closable="current === 1"
    :mask-closable="current === 1"
  >
    <!-- <template #header-extra>
      <NButton v-if="current === 1" text
        ><template #icon
          ><NIcon size="20" :component="PuzzlePiece20Regular" /></template
      ></NButton>
    </template> -->
    <NSteps class="mb-8" :current="(current as number)" :status="currentStatus">
      <NStep title="Pasirink klausimą"> </NStep>
      <NStep title="Sukurk posėdžio įvykį"> </NStep>
    </NSteps>
    <FadeTransition>
      <NForm v-if="current === 1" :model="questionForm">
        <FadeTransition>
          <NAlert
            v-if="showAlert"
            title="Pastebėjimas"
            closable
            class="mb-4"
            type="default"
          >
            <template #icon>
              <NIcon><BookExclamationMark20Filled /></NIcon>
            </template>
            Pasirink arba įrašyk svarbiausią klausimą, kuris
            <strong>bus sprendžiamas posėdyje</strong> (ar yra įtrauktas į
            darbotvarkę).
          </NAlert>
        </FadeTransition>
        <NGrid cols="1">
          <NFormItemGi label="Klausimo pavadinimas" path="title" required>
            <NSelect
              v-model:value="questionForm.title"
              placeholder="Studijų tinklelio peržiūra"
              filterable
              tag
              :options="allQuestionOptions"
              ><template #action>
                <span
                  class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
                  >Gali naudoti ir savo klausimą! Įrašyk +
                  <NTag size="tiny">Enter</NTag></span
                >
              </template></NSelect
            >
          </NFormItemGi>
          <NFormItemGi
            v-if="!isExistingQuestionSelected"
            label="Aprašymas"
            path="description"
          >
            <NInput
              v-model:value="questionForm.description"
              type="textarea"
              placeholder="Aprašykite klausimo kontekstą, jeigu to reikia..."
            ></NInput>
          </NFormItemGi>
          <NFormItemGi :show-label="false">
            <NButton
              v-if="isExistingQuestionSelected"
              :loading="loading"
              type="primary"
              @click="pickQuestion"
              >Panaudoti</NButton
            >
            <NButton
              v-else
              :loading="loading"
              type="primary"
              @click="createQuestion"
              >{{ "Sukurti" }}</NButton
            ></NFormItemGi
          >
        </NGrid>
      </NForm>
    </FadeTransition>
    <FadeTransition>
      <DoingForm
        v-if="current === 2"
        :doing="doingTemplate"
        :question="question"
        :doing-types="doingTypes"
        model-route="doings.store"
        @success="showDoingForm = false"
      ></DoingForm>
    </FadeTransition>
    <div class="absolute bottom-8 right-12">
      <FadeTransition>
        <NButton
          v-if="!showAlert"
          type="tertiary"
          color="#bbbbbb"
          text
          @click="showAlert = true"
          ><template #icon
            ><NIcon size="48" :component="Question24Regular" /></template
        ></NButton>
      </FadeTransition>
    </div>
  </NModal>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  BookExclamationMark20Filled,
  PeopleTeamAdd24Filled,
  PuzzlePiece20Regular,
  Question24Regular,
} from "@vicons/fluent";
import {
  NAlert,
  NButton,
  NButtonGroup,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NModal,
  NSelect,
  NStep,
  NSteps,
  NTag,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import { questionOptions } from "@/Composables/someTypes";
import DoingForm from "@/Components/Admin/Forms/DoingForm.vue";
import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";
import QuickActionButton from "@/Components/Admin/Buttons/QuickActionButton.vue";

const props = defineProps<{
  dutyInstitution: Record<string, any>;
  doingTypes: any;
}>();

const showDoingForm = ref(false);
const loading = ref(false);
const question = ref(null);
const showAlert = ref(false);

const doingTemplate = {
  title: "Planuotas posėdis",
  // type_id where label = "Posėdis"
  type_id: props.doingTypes.find((type: any) => type.label === "Posėdis").value,
  status: "Sukurtas",
  // datetime now YYYY-MM-DD HH:MM:SS and delimit T
  date: null,
};

const current = ref(1);
const currentStatus = ref("process");

const questionForm = useForm({
  title: "",
  description: "",
});

const isExistingQuestionSelected = computed(() => {
  // check if questionForm title is number
  return !isNaN(questionForm.title);
});

const allQuestionOptions = [
  {
    type: "group",
    label: "Sukurti klausimai",
    key: "group1",
    children: props.dutyInstitution.questions.map((question) => ({
      label: question.title,
      value: question.id,
    })),
  },
  {
    type: "group",
    label: "Nauji šabloniniai klausimai",
    key: "group2",
    children: questionOptions,
  },
];

const next = () => {
  if (current.value === null) current.value = 1;
  else current.value++;
};

const createQuestion = () => {
  loading.value = true;
  questionForm.post(
    route("dutyInstitutions.questions.store", {
      dutyInstitution: props.dutyInstitution.id,
    }),
    {
      preserveScroll: true,

      onSuccess: () => {
        loading.value = false;
        console.log(usePage().props.value.flash.data);
        question.value = usePage().props.value.flash.data;
        current.value = 2;
      },
    }
  );
};

const pickQuestion = () => {
  loading.value = true;
  question.value = props.dutyInstitution.questions.find(
    (question) => question.id === questionForm.title
  );
  current.value = 2;
  loading.value = false;
};
</script>
