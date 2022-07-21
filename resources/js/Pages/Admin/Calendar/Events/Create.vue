<template>
  <AdminLayout :title="formValue.title ? formValue.title : 'Naujas įvykis'">
    <UpsertModelLayout :form-value="formValue">
      <NForm ref="formRef" :model="formValue" label-placement="top">
        <NGrid :span="24" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="12" path="title">
            <NInput
              v-model:value="formValue.title"
              type="text"
              placeholder="Įrašyti pavadinimą..."
            />
          </NFormItemGi>

          <NFormItemGi label="Data ir laikas" :span="12" path="date">
            <NDatePicker
              v-model:formatted-value="formValue.date"
              placeholder="Įrašyti laiką..."
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            />
          </NFormItemGi>

          <NFormItemGi label="Aprašymas (HTML)" :span="12" path="description">
            <NInput
              v-model:value="formValue.description"
              type="textarea"
              placeholder="Įrašyti aprašymą..."
            />
          </NFormItemGi>

          <NFormItemGi label="Kategorija" :span="12" path="category">
            <NSelect
              v-model:value="formValue.category"
              :options="options"
              placeholder="Pasirinkti kategoriją..."
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="Nuoroda" :span="12" path="url">
            <NInput
              v-model:value="formValue.url"
              type="text"
              placeholder="Įrašyti nuorodą..."
            />
          </NFormItemGi>
        </NGrid>
      </NForm>
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import {
  FormInst,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGi,
  NGrid,
  NInput,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const formRef = ref<FormInst | null>(null);
const formBlueprint: App.Models.Calendar = {
  title: "",
  date: null,
  description: "",
  category: null,
  url: "",
};

// useForm saves the form value to a remembered state.
const formValue = useForm("calendarCreate", formBlueprint);

const options = [
  {
    value: "red",
    label: "Akademinė informacija",
  },
  {
    value: "yellow",
    label: "Socialinė informacija",
  },
  {
    value: "grey",
    label: "Kita informacija",
  },
];
</script>
