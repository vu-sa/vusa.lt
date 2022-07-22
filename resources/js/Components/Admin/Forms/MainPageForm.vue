<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="12">
        <NInput
          v-model:value="form.text"
          type="text"
          placeholder="Įrašyti pavadinimą"
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda, į kurią nukreipia mygtukas" :span="12">
        <NInput
          v-model:value="form.link"
          type="text"
          placeholder="https://vu.lt"
        />
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end">
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
import { NForm, NFormItemGi, NGrid, NInput } from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  mainPage: App.Models.MainPage;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("mainPage", props.mainPage);
</script>
