<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="24">
        <NDynamicInput v-model:value="customValue" :on-create="onCreate">
          <template #create-button-default> Add whatever you want </template>
          <template #default="{ value }">
            <div style="display: flex; align-items: center; width: 100%">
              <NInput
                v-model:value="value.title"
                placeholder="Laukelio pavadinimas"
                style="margin-right: 12px; width: 33%"
              />
              <NSelect
                v-model:value="value.component"
                placeholder="Pasirinkite laukelio tipą"
                :options="options"
                style="margin-right: 12px; width: 160px"
              />
              <NCheckbox
                v-model:checked="value.required"
                label="Ar privalomas?"
              />
            </div>
          </template>
        </NDynamicInput>
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NCheckbox,
  NDynamicInput,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  registrationForm: App.Models.RegistrationForm;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("registrationForm", props.registrationForm);

const customValue = ref([
  {
    title: "",
    component: null,
    required: false,
  },
]);

const onCreate = () => {
  return {
    title: "",
    component: null,
    required: false,
  };
};

const options = [
  {
    value: "NInput",
    label: "Tekstas",
  },
  {
    value: "textarea",
    label: "Teksto laukelis",
  },
  {
    value: "select",
    label: "Pasirinkimas",
  },
  {
    value: "NCheckbox",
    label: "Checkbox",
  },
  {
    value: "radio",
    label: "Radio",
  },
];
</script>
