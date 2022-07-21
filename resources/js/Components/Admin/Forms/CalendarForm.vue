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

      <NFormItemGi label="Data ir laikas" :span="12">
        <NDatePicker
          v-model:formatted-value="form.date"
          placeholder="Įrašyti laiką..."
          value-format="yyyy-MM-dd HH:mm:ss"
          type="datetime"
        />
      </NFormItemGi>

      <NFormItemGi label="Kategorija" :span="12">
        <NSelect
          v-model:value="form.category"
          :options="options"
          placeholder="Pasirinkti kategoriją..."
          clearable
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda" :span="12">
        <NInput
          v-model:value="form.url"
          type="text"
          placeholder="Įrašyti nuorodą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Aprašymas" :span="24">
        <TipTap
          v-model="form.description"
          :search-files="$page.props.search.other"
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
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  calendar: CalendarEventForm;
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
