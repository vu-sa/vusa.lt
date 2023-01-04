<template>
  <NButton size="small" @click="showMeetingForm = true"
    ><template #icon
      ><NIcon :component="PeopleTeamAdd24Filled"></NIcon></template
    >Pranešti?</NButton
  >
  <CardModal
    v-model:show="showMeetingForm"
    display-directive="show"
    class="prose prose-sm max-w-xl transition dark:prose-invert"
    :title="`${$t('Pranešti apie artėjantį posėdį')}`"
    @close="showMeetingForm = false"
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
        ref="mattersFormRef"
        :rules="mattersFormRules"
        :model="mattersForm"
      >
        <FadeTransition>
          <SuggestionAlert
            :show-alert="showAlert"
            @alert-closed="showAlert = false"
          >
            <p class="inline-flex items-center">
              <span>Kiekvienas posėdis turi</span>
              <ModelChip>
                <template #icon
                  ><NIcon :component="BookQuestionMark20Filled"></NIcon
                ></template>
                svarstomų klausimų</ModelChip
              >
            </p>
            <p class="my-0">
              Patogiausia juos surasti posėdžio darbotvarkėje.
              <strong> Pasirink klausimus</strong>, kurie yra įtraukti į
              darbotvarkę.
            </p>
          </SuggestionAlert>
        </FadeTransition>
        <NGrid cols="1">
          <NFormItemGi label="Svarstomi klausimai" path="idArray">
            <NSelect
              v-model:value="mattersForm.idArray"
              placeholder="Studijų tinklelio peržiūra"
              filterable
              multiple
              :options="existingMatterOptions"
            ></NSelect>
          </NFormItemGi>
          <NFormItemGi :show-label="false">
            <NCheckbox v-model:checked="hasNewMatters"
              ><span
                >Posėdyje yra dar <strong>nesukurtų</strong> svarstomų
                klausimų.</span
              ></NCheckbox
            >
          </NFormItemGi>
          <template v-if="hasNewMatters">
            <NFormItemGi label="Nauji svarstomi klausimai" path="newMatters">
              <NSelect
                v-model:value="mattersForm.newTitleArray"
                placeholder="Studijų tinklelio peržiūra"
                filterable
                multiple
                tag
                :options="newMatterOptions"
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
                    v-model:checked="mattersForm.moreMattersUndefined"
                    class="ml-4 w-fit"
                    ><span class="whitespace-nowrap"
                      >ir kiti...</span
                    ></NCheckbox
                  >
                </template>
                <strong>„ir kiti...“</strong> – leidžia suprasti, kad yra kitų
                svarstomų klausimų, kuriuos sunku apibrėžti.
              </NPopover>
            </NFormItemGi>
            <NFormItemGi label="Aprašymas" path="description">
              <NInput
                v-model:value="mattersForm.newMatterDescription"
                type="textarea"
                placeholder="Aprašykite klausimo (-ų) kontekstą, jeigu to reikia..."
              ></NInput>
            </NFormItemGi>
          </template>
          <NFormItemGi :show-label="false">
            <NButton
              :loading="loading"
              :disabled="!isMatterChosen"
              type="primary"
              @click.prevent="pickMatter"
              >Toliau...</NButton
            >
          </NFormItemGi>
        </NGrid>
      </NForm>
      <MeetingForm
        v-else-if="current === 2"
        :meeting="meetingTemplate"
        :matters-form="mattersForm"
        model-route="meetings.store"
        @success="showMeetingForm = false"
      ></MeetingForm>
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
  </CardModal>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  BookQuestionMark20Filled,
  PeopleTeamAdd24Filled,
  Question24Regular,
} from "@vicons/fluent";
import {
  NButton,
  NCheckbox,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NPopover,
  NSelect,
  NStep,
  NSteps,
  NTag,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import { useStorage } from "@vueuse/core";

import { matterOptions } from "@/Composables/someTypes";
import CardModal from "@/Components/Modals/CardModal.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import ModelChip from "../Chips/ModelChip.vue";
import SuggestionAlert from "../Alerts/SuggestionAlert.vue";

const props = defineProps<{
  institution: Record<string, any>;
}>();

const showMeetingForm = ref(false);
const loading = ref(false);
const current = ref(1);
const currentStatus = ref("process");
const hasNewMatters = ref(false);
const mattersFormRef = ref(null);
const showAlert = useStorage("new-meeting-button-alert", true);

const meetingTemplate = {
  // title: "Planuotas posėdis",
  // type_id where label = "Posėdis"
  // type_id: props.doingTypes.find(
  //   (type: App.Models.Type) => type.label === "Posėdis"
  // )?.value,
  status: "Sukurtas",
  // datetime now YYYY-MM-DD HH:MM:SS and delimit T
  start_time: null,
};

const mattersForm = useForm({
  idArray: [],
  moreMattersUndefined: false,
  newTitleArray: [],
  newMatterDescription: "",
  institution_id: props.institution.id,
});

const existingMatterOptions = [
  {
    type: "group",
    label: "Esami klausimai",
    key: "group1",
    children: props.institution.matters.map((matter: Record<string, any>) => {
      return {
        label: matter.title,
        value: matter.id,
      };
    }),
  },
];

const newMatterOptions = [
  {
    type: "group",
    label: "Nauji šabloniniai klausimai",
    key: "group2",
    children: matterOptions,
  },
];

const isMatterChosen = computed(() => {
  return mattersForm.idArray.length > 0 || mattersForm.newTitleArray.length > 0;
});

const pickMatter = (e: MouseEvent) => {
  loading.value = true;
  mattersFormRef.value?.validate((errors) => {
    if (!errors) {
      current.value = 2;
    }
    loading.value = false;
  });
};

const mattersFormRules = {
  titlesOrIds: {
    required: true,
    type: "array",
    message: "Pasirink (arba įrašyk) bent vieną klausimą",
    trigger: ["blur"],
  },
};
</script>
