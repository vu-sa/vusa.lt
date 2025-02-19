<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <NFormItem required :label="$t('forms.fields.title')" :span="2">
        <NInput v-model:value="form.name" type="text" placeholder="Turinio tipas" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";

const { role, rememberKey } = defineProps<{
  role: App.Entities.Role;
  rememberKey?: "CreateRole";
}>();

const form = rememberKey ? useForm(rememberKey, role) : useForm(role);

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();
</script>
