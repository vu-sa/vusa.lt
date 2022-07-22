<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="8">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą"
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda, į kurią nukreipia paveikslėlis" :span="8">
        <NInput
          v-model:value="form.link_url"
          type="text"
          placeholder="https://vu.lt"
        />
      </NFormItemGi>

      <NFormItemGi label="Ar aktyvus?" :span="8">
        <NSwitch
          v-model:value="form.is_active"
          :checked-value="1"
          :unchecked-value="0"
        />
      </NFormItemGi>

      <NFormItemGi label="Logotipas" :span="24">
        <UploadImageButtons v-model="form.image_url" :path="'banners'" />
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
import { NForm, NFormItemGi, NGrid, NInput, NSwitch } from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UploadImageButtons from "@/components/Admin/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  banner: App.Models.Banner;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("banner", props.banner);
</script>
