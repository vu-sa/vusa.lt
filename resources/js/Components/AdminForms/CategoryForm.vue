<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <NFormItem :label="$t('forms.fields.title')">
        <NInput v-model:value="form.name" type="text" placeholder="Įrašyti pavadinimą..." />
      </NFormItem>
      <NFormItem label="Trumpinys">
        <NInput v-model:value="form.alias" type="text" placeholder="" :disabled="form.id" />
      </NFormItem>
      <NFormItem label="Aprašymas">
        <NInput v-model:value="form.description" type="textarea" placeholder="..." />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";

const { category, rememberKey } = defineProps<{
  category: App.Entities.Category;
  rememberKey?: string;
}>();

const form = rememberKey ? useForm(rememberKey, category) : useForm(category);

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();
</script>
