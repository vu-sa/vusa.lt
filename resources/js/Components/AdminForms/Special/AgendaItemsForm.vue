<template>
  <div>
    <NForm ref="form" :rules="agendaItemRules" :model="agendaItemsForm">
      <FadeTransition>
        <SuggestionAlert :show-alert @alert-closed="showAlert = false">
          <template v-if="$page.props.app.locale === 'lt'">
            <p class="mt-0">
              <span>Kiekvienas posėdis turi</span>
              <ModelChip>
                <template #icon>
                  <NIcon :component="IconsRegular.AGENDA_ITEM" />
                </template>
                darbotvarkės klausimų
              </ModelChip>
            </p>
            <p class="mb-4">
              Įrašyk arba įkopijuok visus klausimus, kurie šiuo metu yra numatomi
              posėdyje.
            </p>
            <p>
              Jeigu žinai, kad posėdis vyks, bet
              <strong>dar nėra klausimų</strong> (arba jų dar bus) –
              <strong> pažymėk varnelę ties </strong>„Vėliau gali atsirasti
              papildomų klausimų“
            </p>
          </template>
          <template v-else>
            <p class="mt-0">
              <span>Each meeting has</span>
              <ModelChip>
                <template #icon>
                  <NIcon :component="IconsRegular.AGENDA_ITEM" />
                </template>
                agenda items
              </ModelChip>
            </p>
            <p class="mb-4">
              Enter or paste all the questions that are currently planned for the
              meeting.
            </p>
            <p>
              If you know that the meeting will take place, but
              <strong>there are no questions</strong> (or there will be more) –
              <strong> check the box next to </strong>“More agenda items may
              appear later”
            </p>
          </template>
        </SuggestionAlert>
      </FadeTransition>
      <NFormItem path="agendaItemTitles">
        <template #label>
          <span class="mb-2 inline-flex items-center gap-1">
            <NIcon :component="IconsFilled.AGENDA_ITEM" />
            {{ $t("Darbotvarkės klausimai") }}
          </span>
        </template>
        <div class="flex grow flex-col gap-2">
          <NDynamicInput v-if="!showQuestionInputInTextArea" v-model:value="agendaItemsForm.agendaItemTitles"
            show-sort-button>
            <template #create-button-default>
              {{ $t("Pridėti po vieną") }}
            </template>
            <template #default="{ index }">
              <div class="flex grow items-center gap-1">
                <span class="ml-1 w-7">{{ `${index + 1}.` }}</span>
                <NInput v-model:value="agendaItemsForm.agendaItemTitles[index]"
                  :placeholder="`Darbotvarkės klausimas nr. ${index + 1}`" @keydown.enter.prevent />
              </div>
            </template>
          </NDynamicInput>

          <div v-else class="flex flex-col items-start gap-2">
            <NInput v-model:value="questionInputInTextArea" type="textarea" class="w-full" rows="5"
              :placeholder="$page.props.app.locale === 'lt' ? 'Kiekvienas klausimas turi būti iš naujos eilutės, pvz.:\n\nKlausimas nr. 1\nKlausimas nr. 2' : 'Every question must begin from new line, e.g.\n\nQuestion no. 1\nQuestion no. 2'" />
            <div class="flex w-full justify-between gap-2">
              <NButton size="small" @click="showQuestionInputInTextArea = false">
                {{ $t('Grįžti') }}
              </NButton>
              <NButton type="primary" size="small" @click="handleQuestionsFromTextArea">
                {{ $t('Įkelti iš teksto') }}
              </NButton>
            </div>
          </div>
          <NButton v-if="
            agendaItemsForm.agendaItemTitles.length === 0 &&
            !showQuestionInputInTextArea
          " @click="showQuestionInputInTextArea = true">
            <template #icon>
              <IFluentDocumentQueueAdd20Regular />
            </template>
            {{ $t('Įkelti iš teksto') }}
          </NButton>
        </div>
      </NFormItem>
      <NFormItem>
        <template #label>
          <span class="inline-flex items-center gap-1">
            <IFluentDocumentSettings20Filled />
            {{ $t("forms.context.additional_info") }}
          </span>
        </template>
        <NCheckbox v-model:checked="agendaItemsForm.moreAgendaItemsUndefined">
          <span class="whitespace-nowrap">{{
            $t("Vėliau gali atsirasti papildomų darbotvarkės klausimų")
          }}</span>
        </NCheckbox>
      </NFormItem>
      <NFormItem :show-label="false">
        <NButton :loading="loading" :disabled="agendaItemsForm.agendaItemTitles.length === 0 &&
          !agendaItemsForm.moreAgendaItemsUndefined
          " type="primary" @click.prevent="submitForm">
          {{ $t("forms.submit") }}
        </NButton>
      </NFormItem>
    </NForm>
  </div>
</template>

<script setup lang="tsx">
import {
  type FormInst,
  type FormRules,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import IconsFilled from "@/Types/Icons/filled";
import IconsRegular from "@/Types/Icons/regular";
import ModelChip from "@/Components/Chips/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

const emit = defineEmits<{
  (e: "submit", data: Record<string, any>): void;
}>();

defineProps<{
  //   institution: App.Entities.Institution;
  loading: boolean;
}>();

const showAlert = useStorage("new-meeting-button-alert", true);
const showQuestionInputInTextArea = ref(false);
const questionInputInTextArea = ref("");

const handleQuestionsFromTextArea = () => {
  // split question by new line and put them into agendaItemsForm.agendaItemTitles
  const questions = questionInputInTextArea.value.split("\n");
  agendaItemsForm.agendaItemTitles = questions;
  showQuestionInputInTextArea.value = false;
};

const form = ref<FormInst | null>(null);

const agendaItemsForm = useForm({
  moreAgendaItemsUndefined: false,
  agendaItemTitles: [],
  //   newMatterDescription: "",
  //   institution_id: props.institution.id,
});

const agendaItemRules: FormRules = {
  titlesOrIds: {
    required: true,
    type: "array",
    message: "Pasirink (arba įrašyk) bent vieną klausimą",
    trigger: ["blur"],
  },
  agendaItemTitles: {
    required: true,
    trigger: ["blur"],
    validator: (rule: unknown, value) => {
      if (value.some((title: string) => [null, ""].includes(title))) {
        return new Error("Klausimas negali būti tuščias");
      }
      if (agendaItemsForm.moreAgendaItemsUndefined) {
        return true;
      }
      if (value.length === 0) {
        return new Error("Pasirink (arba įrašyk) bent vieną klausimą");
      }
      return true;
    },
  },
};

const submitForm = () => {
  form.value?.validate((errors) => {
    if (!errors) {
      emit("submit", agendaItemsForm);
    }
  });
};
</script>
