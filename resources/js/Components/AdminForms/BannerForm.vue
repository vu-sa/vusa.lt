<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="2">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą"
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda, į kurią nukreipia paveikslėlis" :span="2">
        <NInput
          v-model:value="form.link_url"
          type="text"
          placeholder="https://vu.lt"
        />
      </NFormItemGi>

      <NFormItemGi label="Ar aktyvus?" :span="2">
        <NSwitch
          v-model:value="form.is_active"
          :checked-value="1"
          :unchecked-value="0"
        />
      </NFormItemGi>

      <NFormItemGi label="Logotipas" :span="2">
        <UploadImageButtons v-model="form.image_url" :path="'banners'" />
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
import { NForm, NFormItemGi, NGrid, NInput, NSwitch } from "naive-ui";
import { useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  banner: App.Entities.Banner;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("banner", props.banner);
</script>
