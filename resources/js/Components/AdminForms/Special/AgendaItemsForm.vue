<template>
  <NForm :rules="agendaItemRules" :model="agendaItemForm">
    <FadeTransition>
      <SuggestionAlert
        :show-alert="showAlert"
        @alert-closed="showAlert = false"
      >
        <p class="inline-flex items-center">
          <span>Kiekvienas posėdis turi</span>
          <ModelChip>
            <template #icon
              ><NIcon :component="Icons.AGENDA_ITEM"></NIcon
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
      <NFormItemGi label="Nauji svarstomi klausimai" path="newMatters">
        <NSelect
          v-model:value="agendaItemForm.agendaItemTitles"
          placeholder="Studijų tinklelio peržiūra"
          filterable
          multiple
          tag
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
              v-model:checked="agendaItemForm.moreAgendaItemsUndefined"
              class="ml-4 w-fit"
              ><span class="whitespace-nowrap">ir kiti...</span></NCheckbox
            >
          </template>
          <strong>„ir kiti...“</strong> – leidžia suprasti, kad yra kitų
          svarstomų klausimų, kuriuos sunku apibrėžti.
        </NPopover>
      </NFormItemGi>
      <!-- <NFormItemGi label="Aprašymas" path="description">
        <NInput
          v-model:value="mattersForm.newMatterDescription"
          type="textarea"
          placeholder="Aprašykite klausimo (-ų) kontekstą, jeigu to reikia..."
        ></NInput>
      </NFormItemGi> -->
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
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NPopover,
  NSelect,
  NTag,
  //   type SelectGroupOption,
} from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Icons from "@/Types/Icons/regular";
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
