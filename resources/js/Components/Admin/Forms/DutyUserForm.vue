<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Studijų programa" :span="12">
        <NInput
          v-model:value="form.attributes.study_program"
          type="text"
          placeholder="Įrašyti tekstą..."
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
import { NForm, NFormItemGi, NGrid, NInput } from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  dutyUser: App.Models.MainPage;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutyUser", props.dutyUser);

if (!form.attributes) {
  form.attributes = {};
}

if (!form.attributes.study_program) {
  form.attributes.study_program = "";
}
</script>
