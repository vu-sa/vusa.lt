<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Mygtuko tekstas" :span="12">
        <NInput
          v-model:value="form.text"
          type="text"
          placeholder="Įrašyti tekstą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda, į kurią nukreipia mygtukas" :span="12">
        <NInput
          v-model:value="form.link"
          type="text"
          placeholder="/stipendijos | https://vu.lt"
        />
      </NFormItemGi>
      <NFormItemGi label="Kalba" :span="12">
        <NSelect
          v-model:value="form.lang"
          :options="languageOptions"
          placeholder="Pasirinkti kalbą..."
        />
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
import { NForm, NFormItemGi, NGrid, NInput, NSelect } from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  mainPage: App.Models.MainPage;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("mainPage", props.mainPage);

const languageOptions = [
  {
    value: "lt",
    label: "Lietuvių",
  },
  {
    value: "en",
    label: "English",
  },
];
</script>
