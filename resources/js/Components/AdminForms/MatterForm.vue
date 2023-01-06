<template>
  <NForm :model="matterForm">
    <NGrid cols="1">
      <NFormItemGi label="Klausimo pavadinimas" path="title" required>
        <NSelect
          v-model:value="matterForm.title"
          placeholder="Studijų tinklelio peržiūra"
          filterable
          tag
          :options="matterOptions"
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
          v-model:value="matterForm.description"
          type="textarea"
          placeholder="Aprašykite klausimo kontekstą, jeigu to reikia..."
        ></NInput>
      </NFormItemGi>
      <!-- <NFormItemGi label="Statusas" path="status" required :span="2">
        <NRadioGroup v-model:value="matterForm.status">
          <NRadio
            v-for="status in matterStatusOptions"
            :key="status.value"
            :value="status.value"
            ><StatusTag :status="status.label"></StatusTag
          ></NRadio>
        </NRadioGroup>
      </NFormItemGi> -->
      <NFormItemGi :show-label="false"
        ><NButton type="primary" @click="createMatter"
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


import { matterOptions } from "@/Composables/someTypes";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const emit = defineEmits<{
  (e: "matterStored"): void;
}>();

const props = defineProps<{
  institution: App.Models.Institution;
  form: any;
}>();

const matterForm = useForm(props.form);

const matterStatusOptions = [
  {
    label: "Sukurtas",
    value: "Sukurtas",
  },
  {
    label: "Pabaigtas",
    value: "Pabaigtas",
  },
];

const createMatter = () => {
  matterForm.post(
    route("matters.store", {
      institution_id: props.institution.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("matterStored");
        matterForm.reset();
      },
    }
  );
};
</script>
