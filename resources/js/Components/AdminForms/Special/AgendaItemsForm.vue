<template>
  <NForm :rules="agendaItemRules" :model="agendaItemForm">
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
        <p class="my-0">
          Įrašyk arba įkopijuok visus klausimus, kurie šiuo metu yra numatomi
          posėdyje.
        </p>
      </SuggestionAlert>
    </FadeTransition>
    <NGrid cols="1">
      <NFormItemGi path="newAgendaTitles">
        <template #label>
          <span class="mb-2 inline-flex items-center gap-1"
            ><NIcon :component="IconsFilled.AGENDA_ITEM"></NIcon>Darbotvarkės
            klausimai</span
          >
        </template>
        <NDynamicInput
          v-model:value="agendaItemForm.agendaItemTitles"
          show-sort-button
        >
          <template #create-button-default>Sukurti</template>
          <template #default="{ index, value }">
            <div class="flex w-full items-center gap-1">
              <span class="ml-1 w-7">{{ `${index + 1}.` }}</span>
              <NInput
                v-model:value="agendaItemForm.agendaItemTitles[index]"
                :placeholder="`Darbotvarkės klausimas nr. ${index + 1}`"
                @keydown.enter.prevent
              ></NInput>
            </div>
          </template>
        </NDynamicInput>
      </NFormItemGi>
      <NFormItemGi>
        <template #label>
          <span class="inline-flex items-center gap-1"
            ><NIcon :component="DocumentSettings20Filled"></NIcon>Papildoma
            informacija</span
          >
        </template>
        <NCheckbox v-model:checked="agendaItemForm.moreAgendaItemsUndefined"
          ><span class="whitespace-nowrap"
            >Vėliau gali atsirasti papildomų klausimų</span
          ></NCheckbox
        >
      </NFormItemGi>
      <NFormItemGi :show-label="false">
        <NButton
          :loading="loading"
          :disabled="agendaItemForm.agendaItemTitles.length === 0"
          type="primary"
          @click.prevent="$emit('submit', agendaItemForm)"
          >Sukurti</NButton
        >
      </NFormItemGi>
    </NGrid>
  </NForm>
</template>

<script setup lang="tsx">
import {
  NButton,
  NCheckbox,
  NDynamicInput,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NPopover,
  NSelect,
  NTag,
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

const agendaItemForm = useForm({
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
