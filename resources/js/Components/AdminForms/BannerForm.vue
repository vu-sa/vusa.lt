<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <FormFieldWrapper id="title" :label="$t('forms.fields.title')">
        <Input id="title" v-model="form.title" type="text" placeholder="Įrašyti pavadinimą" />
      </FormFieldWrapper>

      <FormFieldWrapper id="link_url" label="Nuoroda, į kurią nukreipia paveikslėlis">
        <Input id="link_url" v-model="form.link_url" type="text" placeholder="https://vu.lt" />
      </FormFieldWrapper>

      <FormFieldWrapper id="is_active" label="Ar aktyvus?">
        <Switch :checked="!!form.is_active" @update:checked="val => form.is_active = val ? 1 : 0" />
      </FormFieldWrapper>

      <FormFieldWrapper id="image_url" label="Paveikslėlis">
        <ImageUpload v-model:url="form.image_url" mode="immediate" folder="banners" cropper :existing-url="banner?.image_url" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import { ImageUpload } from '@/Components/ui/upload';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const { banner, rememberKey } = defineProps<{
  banner: App.Entities.Banner;
  rememberKey?: string;
}>();

const form = rememberKey ? useForm(rememberKey, banner) : useForm(banner);
</script>
