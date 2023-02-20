<template>
  <NForm :rules="agendaItemRules" :model="agendaItemsForm">
    <FadeTransition>
      <SuggestionAlert
        :show-alert="showAlert"
        @alert-closed="showAlert = false"
      >
        <p class="mt-0">
          <span>Kiekvienas posėdis turi</span>
          <ModelChip>
            <template #icon
              ><NIcon :component="IconsRegular.AGENDA_ITEM"></NIcon
            ></template>
            darbotvarkės klausimų</ModelChip
          >
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
      </SuggestionAlert>
    </FadeTransition>
    <NFormItem path="newAgendaTitles">
      <template #label>
        <span class="mb-2 inline-flex items-center gap-1"
          ><NIcon :component="IconsFilled.AGENDA_ITEM"></NIcon>
          {{ $t("Darbotvarkės klausimai") }}
        </span>
      </template>
      <NDynamicInput
        v-model:value="agendaItemsForm.agendaItemTitles"
        show-sort-button
      >
        <template #create-button-default>{{ $t("forms.add") }}</template>
        <template #default="{ index }">
          <div class="flex grow items-center gap-1">
            <span class="ml-1 w-7">{{ `${index + 1}.` }}</span>
            <NInput
              v-model:value="agendaItemsForm.agendaItemTitles[index]"
              :placeholder="`Darbotvarkės klausimas nr. ${index + 1}`"
              @keydown.enter.prevent
            ></NInput>
          </div>
        </template>
      </NDynamicInput>
    </NFormItem>
    <NFormItem>
      <template #label>
        <span class="inline-flex items-center gap-1"
          ><NIcon :component="DocumentSettings20Filled" />
          {{ $t("forms.context.additional_info") }}
        </span>
      </template>
      <NCheckbox v-model:checked="agendaItemsForm.moreAgendaItemsUndefined"
        ><span class="whitespace-nowrap">{{
          $t("Vėliau gali atsirasti papildomų darbotvarkės klausimų")
        }}</span></NCheckbox
      >
    </NFormItem>
    <NFormItem :show-label="false">
      <NButton
        :loading="loading"
        :disabled="
          agendaItemsForm.agendaItemTitles.length === 0 &&
          !agendaItemsForm.moreAgendaItemsUndefined
        "
        type="primary"
        @click.prevent="$emit('submit', agendaItemsForm)"
        >{{ $t("forms.submit") }}</NButton
      >
    </NFormItem>
  </NForm>
</template>

<script setup lang="tsx">
import {
  NButton,
  NCheckbox,
  NDynamicInput,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  //   type SelectGroupOption,
} from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import { DocumentSettings20Filled } from "@vicons/fluent";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import IconsFilled from "@/Types/Icons/filled";
import IconsRegular from "@/Types/Icons/regular";
import ModelChip from "@/Components/Chips/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

defineEmits<{
  (e: "submit", data: Record<string, any>): void;
}>();

defineProps<{
  //   institution: App.Entities.Institution;
  loading: boolean;
}>();

const showAlert = useStorage("new-meeting-button-alert", true);

const agendaItemsForm = useForm({
  moreAgendaItemsUndefined: false,
  agendaItemTitles: [],
  //   newMatterDescription: "",
  //   institution_id: props.institution.id,
});

const agendaItemRules = {
  titlesOrIds: {
    required: true,
    type: "array",
    message: "Pasirink (arba įrašyk) bent vieną klausimą",
    trigger: ["blur"],
  },
};
</script>
