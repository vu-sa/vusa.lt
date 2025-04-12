<template>
  <NForm :model="matterForm">
    <div class="grid grid-cols-1 gap-4">
      <NFormItem label="Klausimo pavadinimas" path="title" required>
        <NSelect
          v-model:value="matterForm.title"
          placeholder="Studijų tinklelio peržiūra"
          filterable
          tag
          :options="defaultOptions"
        >
          <template #action>
            <span class="text-xs text-zinc-400">
              Gali įrašyti ir savo klausimą...
            </span>
          </template>
        </NSelect>
      </NFormItem>
      <NFormItem label="Aprašymas" path="description">
        <NInput
          v-model:value="matterForm.description"
          type="textarea"
          placeholder="Aprašykite klausimo kontekstą, jeigu to reikia..."
        ></NInput>
      </NFormItem>
      <NFormItem :show-label="false">
        <NButton :loading="loading" type="primary" @click="handleSubmit">
          Sukurti
        </NButton>
      </NFormItem>
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { NButton, NForm, NFormItem, NInput, NSelect } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { modelDefaults } from "@/Types/formOptions";

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
