<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="12">
        <NInput
          v-model:value="form.subject_name"
          type="text"
          placeholder="Pavadinimas"
        />
      </NFormItemGi>

      <NFormItemGi label="Užregistravusio vardas" :span="12">
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Įrašyti užsiregistravusio vardą.."
        />
      </NFormItemGi>

      <NFormItemGi label="Egzamino tipas" :span="6">
        <NSelect
          v-model:value="form.exam_type"
          :options="examOptions"
          placeholder="Koliokviumas"
          clearable
        />
      </NFormItemGi>

      <NFormItemGi label="Padalinys" :span="6">
        <NSelect
          v-model:value="form.padalinys_id"
          :options="padaliniaiOptions"
          placeholder="Koliokviumas"
          clearable
        />
      </NFormItemGi>
      <NFormItemGi label="Telefonas" :span="12">
        <NInput
          v-model:value="form.phone"
          type="text"
          placeholder="Įrašyti telefoną..."
        />
      </NFormItemGi>
      <NFormItemGi label="El. paštas arba kontaktinė informacija" :span="12">
        <NInput
          v-model:value="form.email"
          type="text"
          placeholder="Įrašyti el. paštą..."
        />
      </NFormItemGi>
      <NFormItemGi label="Egzamino vieta" :span="12">
        <NInput
          v-model:value="form.place"
          type="text"
          placeholder="Įrašyti egzamino vietą..."
        />
      </NFormItemGi>
      <NFormItemGi label="Trukmė" :span="12">
        <NInput
          v-model:value="form.duration"
          type="textarea"
          placeholder="Įrašyti egzamino trukmę..."
        />
      </NFormItemGi>
      <NFormItemGi label="Laikančiųjų skaičius" :span="6">
        <NInputNumber v-model:value="form.exam_holders" />
      </NFormItemGi>
      <NFormItemGi label="Prašytas stebėtojų skaičius" :span="6">
        <NInputNumber v-model:value="form.students_need" />
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NInputNumber,
  NSelect,
} from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  exam: App.Models.SaziningaiExam;
  padaliniai: App.Models.Padalinys[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("saziningaiExam", props.exam);

const examOptions = [
  {
    value: "koliokviumas",
    label: "Koliokviumas",
  },
  {
    value: "egzaminas",
    label: "Egzaminas",
  },
];

const padaliniaiOptions = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname_vu,
}));
</script>
