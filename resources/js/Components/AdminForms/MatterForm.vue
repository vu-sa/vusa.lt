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

import { modelNameDefaultOptions } from "@/Composables/someTypes";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const emit = defineEmits<{
  (e: "matterStored"): void;
}>();

const props = defineProps<{
  institution: App.Entities.Institution;
  form: any;
}>();

const matterForm = useForm(props.form);

const defaultOptions = modelNameDefaultOptions.map((option) => {
  return {
    label: option,
    value: option,
  };
});

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
