<template>
  <FormPage
    :title="isCreate ? $t('Nauja rolė') : form.name"
    entity-type="role"
    :back-href="isCreate ? route('roles.index') : route('roles.show', role.id)"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    max-width="2xl"
    @submit="emit('submit:form', form)"
  >
    <FormSection :title="$t('Rolės pavadinimas')">
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required :error="form.errors.name">
        <Input id="name" v-model="form.name" type="text" :placeholder="$t('forms.placeholders.enter_title')" />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm, type InertiaForm } from '@inertiajs/vue3';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { FormSection } from '@/Components/Patterns';
import { Input } from '@/Components/ui/input';

const { role, rememberKey } = defineProps<{
  role: { id?: string; name: string };
  rememberKey?: string;
}>();

const isCreate = !role.id;
const form = rememberKey ? useForm(rememberKey, { name: role.name }) : useForm({ name: role.name });

const emit = defineEmits<{
  (event: 'submit:form', form: InertiaForm<{ name: string }>): void;
}>();
</script>
