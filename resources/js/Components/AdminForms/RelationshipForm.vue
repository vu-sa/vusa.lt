<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <NFormItem required :label="$t('forms.fields.title')">
        <NInput v-model:value="form.name" type="text" placeholder="Trumpas ryšio pavadinimas.." />
      </NFormItem>
      <NFormItem label="Techninė žymė">
        <NInput v-model:value="form.slug" type="text" placeholder="pvz.: simple-advisory" />
      </NFormItem>
      <NFormItem label="Aprašymas">
        <NInput v-model:value="form.description" type="textarea" placeholder="Trumpas apibūdinimas..." />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";

const { relationship } = defineProps<{
  relationship: App.Entities.Relationship;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("relationship", relationship);
</script>
