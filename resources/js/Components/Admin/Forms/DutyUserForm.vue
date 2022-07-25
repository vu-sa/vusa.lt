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
      <NFormItemGi label="Aprašymas" :span="24">
        <TipTap
          v-model="form.attributes.info_text"
          :search-files="$page.props.search.other"
        />
      </NFormItemGi>
      <NFormItemGi
        label="Papildoma kuratoriaus nuotrauka (naudoti tuo atveju, kai asmuo turi daugiau nei vieną nuotrauką, pvz.: nes turi koordinatoriaus pareigybės nuotrauką)"
        :span="24"
      >
        <UploadImageButtons
          v-model="form.attributes.additional_photo"
          :path="'contacts'"
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
import TipTap from "@/Components/TipTap.vue";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UploadImageButtons from "@/Components/Admin/Buttons/UploadImageButtons.vue";
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

if (!form.attributes.info_text) {
  form.attributes.info_text = "";
}

if (!form.attributes.additional_photo) {
  form.attributes.additional_photo = "";
}
</script>
