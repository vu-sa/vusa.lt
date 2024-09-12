<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <NFormItem :label="$t('forms.fields.title')">
        <NInput v-model:value="form.title" type="text" placeholder="Įrašyti pavadinimą" />
      </NFormItem>

      <NFormItem label="Nuoroda, į kurią nukreipia paveikslėlis">
        <NInput v-model:value="form.link_url" type="text" placeholder="https://vu.lt" />
      </NFormItem>

      <NFormItem label="Ar aktyvus?">
        <NSwitch v-model:value="form.is_active" :checked-value="1" :unchecked-value="0" />
      </NFormItem>

      <NFormItem label="Paveikslėlis">
        <UploadImageWithCropper v-model:url="form.image_url" folder="banners" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const { banner } = defineProps<{
  banner: App.Entities.Banner;
}>();

const form = useForm("banner", banner);
</script>
