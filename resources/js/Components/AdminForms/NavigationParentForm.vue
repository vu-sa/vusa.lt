<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        Pagrindinė informacija
      </template>
      <template #description>
        Šis navigacijos elementas yra pagrindinis, todėl kiti įprastų elementų nustatymai nėra pasiekiami.
      </template>
      <div class="grid gap-3 lg:grid-cols-2">
        <FormFieldWrapper id="name" label="Pavadinimas" required :error="form.errors.name">
          <Input id="name" v-model="form.name" type="text" placeholder="Įrašyti pavadinimą..." />
        </FormFieldWrapper>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { Input } from '@/Components/ui/input';

const { navigation, rememberKey } = defineProps<{
  navigation: App.Entities.Navigation;
  rememberKey?: 'CreateNavigationParent';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, navigation) : useForm(navigation);
</script>
