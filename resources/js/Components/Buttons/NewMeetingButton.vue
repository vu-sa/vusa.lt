<template>
  <NButton size="small" @click="showDoingForm = true"
    ><template #icon
      ><NIcon :component="PeopleTeamAdd24Filled"></NIcon></template
    >Pranešti?</NButton
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
    @update:show="current = 1"
  >
    <!-- <template #header-extra>
      <NButton v-if="current === 1" text
        ><template #icon
          ><NIcon size="20" :component="PuzzlePiece20Regular" /></template
      ></NButton>
    </template> -->
    <NSteps class="mb-8" :current="(current as number)" :status="currentStatus">
      <NStep title="Pasirink klausimus"> </NStep>
      <NStep title="Sukurk posėdžio įvykį"> </NStep>
    </NSteps>
    <FadeTransition mode="out-in">
      <NForm
        v-if="current === 1"
        ref="questionFormRef"
        :rules="questionFormRules"
        :model="questionForm"
      >
        <FadeTransition>
          <NAlert
            v-if="showAlert"
            style="background-color: rgba(0, 0, 0, 0)"
            title="Įsidėmėk!"
            closable
            class="mb-4"
            type="default"
            @close="showAlert = false"
          >
            <template #icon>
              <NIcon><BookExclamationMark20Filled /></NIcon>
            </template>
            <p class="inline-flex items-center">
              <span>Kiekvienas posėdis svarsto</span>
              <NTag class="mx-2" :bordered="false" round size="small">
                <template #icon
                  ><NIcon :component="BookQuestionMark20Filled"></NIcon
                ></template>
                <strong>klausimus</strong>
              </NTag>
            </p>
            <p class="my-0">
              Patogiausia juos surasti posėdžio darbotvarkėje.
              <strong> Pasirink arba įrašyk klausimus</strong>, kurie yra
              įtraukti į darbotvarkę.
            </p>
          </NAlert>
        </FadeTransition>
        <NGrid cols="1">
          <NFormItemGi label="Klausimo pavadinimas" path="titlesOrIds" required>
            <NSelect
              v-model:value="questionForm.titlesOrIds"
              placeholder="Studijų tinklelio peržiūra"
              filterable
              multiple
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
            <NPopover>
              <template #trigger>
                <NCheckbox
                  v-model:checked="questionForm.andOther"
                  class="ml-4 w-fit"
                  ><span class="whitespace-nowrap">ir kiti...</span></NCheckbox
                >
              </template>
              <strong>„ir kiti...“</strong> – leidžia suprasti, kad yra kitų, be
              jau pažymėtų, svarstomų klausimų.
            </NPopover>
          </NFormItemGi>
          <NFormItemGi
            v-if="!isExistingQuestionSelected"
            label="Aprašymas"
            path="description"
          >
            <NInput
              v-model:value="questionForm.description"
              type="textarea"
              placeholder="Aprašykite klausimo (-ų) kontekstą, jeigu to reikia..."
            ></NInput>
          </NFormItemGi>
          <NFormItemGi :show-label="false">
            <NButton
              :loading="loading"
              type="primary"
              @click.prevent="pickQuestion"
              >Toliau...</NButton
            >
          </NFormItemGi>
        </NGrid>
      </NForm>
      <DoingForm
        v-else-if="current === 2"
        :doing="doingTemplate"
        :doing-types="doingTypes"
        :question-form="questionForm"
        model-route="doings.store"
        @success="showDoingForm = false"
      ></DoingForm>
    </FadeTransition>
    <div class="absolute bottom-8 right-12">
      <FadeTransition>
        <NButton
          v-if="!showAlert && current === 1"
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
  BookQuestionMark20Filled,
  PeopleTeamAdd24Filled,
  PuzzlePiece20Regular,
  Question24Regular,
} from "@vicons/fluent";
import {
  NAlert,
  NButton,
  NButtonGroup,
  NCheckbox,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NModal,
  NPopover,
  NSelect,
  NStep,
  NSteps,
  NTag,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import { useStorage } from "@vueuse/core";
import route from "ziggy-js";

import { questionOptions } from "@/Composables/someTypes";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const props = defineProps<{
  dutyInstitution: Record<string, any>;
  doingTypes: any;
}>();

const showDoingForm = ref(false);
const loading = ref(false);
const showAlert = useStorage("new-meeting-button-alert", true);

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
  titlesOrIds: [],
  description: "",
  andOther: false,
  institution_id: props.dutyInstitution.id,
});

const questionFormRef = ref(null);

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

const pickQuestion = (e: MouseEvent) => {
  loading.value = true;
  questionFormRef.value?.validate((errors) => {
    if (!errors) {
      current.value = 2;
    }
    loading.value = false;
  });
};

const questionFormRules = {
  titlesOrIds: {
    required: true,
    type: "array",
    message: "Pasirink (arba įrašyk) bent vieną klausimą",
    trigger: ["blur"],
  },
};
</script>
