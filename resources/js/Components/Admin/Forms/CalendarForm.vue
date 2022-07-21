<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="12">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Data ir laikas" :span="12" path="date">
        <NDatePicker
          v-model:formatted-value="form.date"
          placeholder="Įrašyti laiką..."
          value-format="yyyy-MM-dd HH:mm:ss"
          type="datetime"
        />
      </NFormItemGi>

      <NFormItemGi label="Aprašymas (HTML)" :span="12" path="description">
        <NInput
          v-model:value="form.description"
          type="textarea"
          placeholder="Įrašyti aprašymą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Kategorija" :span="12" path="category">
        <NSelect
          v-model:value="form.category"
          :options="options"
          placeholder="Pasirinkti kategoriją..."
          clearable
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda" :span="12" path="url">
        <NInput
          v-model:value="form.url"
          type="text"
          placeholder="Įrašyti nuorodą..."
        />
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute"
        >Sukurti</UpsertModelButton
      >
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
} from "naive-ui";
import { reactive } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

interface CalendarFormInteraface
  extends Pick<
    App.Models.Calendar,
    "title" | "date" | "description" | "category" | "url"
  > {
  id?: number;
}

const props = defineProps<{
  calendar: CalendarFormInteraface;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("calendar", props.calendar);

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
