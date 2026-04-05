<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <Input id="name" v-model="form.name" type="text" placeholder="Turinio tipas" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { Input } from '@/Components/ui/input';

const { role, rememberKey } = defineProps<{
  role: App.Entities.Role;
  rememberKey?: 'CreateRole';
}>();

const form = rememberKey ? useForm(rememberKey, role) : useForm(role);

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();
</script>
