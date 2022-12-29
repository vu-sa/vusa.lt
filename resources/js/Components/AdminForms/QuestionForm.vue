<template>
  <NForm :model="questionForm">
    <NGrid cols="1">
      <NFormItemGi label="Klausimo pavadinimas" path="title" required>
        <NSelect
          v-model:value="questionForm.title"
          placeholder="Studijų tinklelio peržiūra"
          filterable
          tag
          :options="questionOptions"
          ><template #action>
            <span
              class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
              >Gali įrašyti ir savo klausimą...</span
            >
          </template></NSelect
        >
      </NFormItemGi>
      <NFormItemGi label="Aprašymas" path="description">
        <NInput
          v-model:value="questionForm.description"
          type="textarea"
          placeholder="Aprašykite klausimo kontekstą, jeigu to reikia..."
        ></NInput>
      </NFormItemGi>
      <!-- <NFormItemGi label="Statusas" path="status" required :span="2">
        <NRadioGroup v-model:value="questionForm.status">
          <NRadio
            v-for="status in questionStatusOptions"
            :key="status.value"
            :value="status.value"
            ><StatusTag :status="status.label"></StatusTag
          ></NRadio>
        </NRadioGroup>
      </NFormItemGi> -->
      <NFormItemGi :show-label="false"
        ><NButton type="primary" @click="createQuestion"
          >Sukurti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="tsx">
import {
  NButton,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NRadio,
  NRadioGroup,
  NSelect,
} from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import { questionOptions } from "@/Composables/someTypes";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const emit = defineEmits<{
  (e: "questionStored"): void;
}>();

const props = defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  form: any;
}>();

const questionForm = useForm(props.form);

const questionStatusOptions = [
  {
    label: "Sukurtas",
    value: "Sukurtas",
  },
  {
    label: "Pabaigtas",
    value: "Pabaigtas",
  },
];

const createQuestion = () => {
  questionForm.post(
    route("questions.store", {
      duty_institution_id: props.dutyInstitution.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("questionStored");
        questionForm.reset();
      },
    }
  );
};
</script>
