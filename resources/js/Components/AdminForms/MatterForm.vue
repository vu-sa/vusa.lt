<template>
  <NForm :model="matterForm">
    <NGrid cols="1">
      <NFormItemGi label="Klausimo pavadinimas" path="title" required>
        <NSelect
          v-model:value="matterForm.title"
          placeholder="Studijų tinklelio peržiūra"
          filterable
          tag
          :options="defaultOptions"
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
        ><NButton :loading="loading" type="primary" @click="handleSubmit"
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
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import { modelDefaults, modelStatus } from "@/Types/formOptions";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const emit = defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  institution: App.Entities.Institution;
  form: any;
}>();

const matterForm = useForm(props.form);
const loading = ref(false);

const defaultOptions = modelDefaults.matter.map((option) => {
  return {
    label: option,
    value: option,
  };
});

const handleSubmit = () => {
  loading.value = true;
  emit("submit", matterForm);
};

// const matterStatusOptions = modelStatus.matter.map((option) => {
//   return {
//     label: option,
//     value: option,
//   };
// });

// const existingMatterOptions: Array<SelectGroupOption> = [
//   {
//     type: "group",
//     label: "Esami klausimai",
//     key: "group1",
//     children:
//       props.institution.matters?.map((matter: Record<string, any>) => {
//         return {
//           label: matter.title,
//           value: matter.id,
//         };
//       }) ?? [],
//   },
// ];

// wrap option into array
// const newMatterOptions = [
//   {
//     type: "group",
//     label: "Nauji šabloniniai klausimai",
//     key: "group2",
//     children: matterOptions,
//   },
// ];

// const isMatterChosen = computed(() => {
//   return mattersForm.idArray.length > 0 || mattersForm.newTitleArray.length > 0;
// });
</script>
