<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          Pagrindinė informacija
        </template>
        <template #description>
          Šis navigacijos elementas yra pagrindinis, todėl kiti įprastų elementų nustatymai nėra pasiekiami.
        </template>
        <div class="grid gap-3 lg:grid-cols-2">
          <NFormItem required label="Pavadinimas">
            <NInput v-model:value="form.name" type="text" placeholder="Įrašyti pavadinimą..." />
          </NFormItem>
        </div>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
      <UpsertModelButton :form="form" :model-route="modelRoute">
        Sukurti
      </UpsertModelButton>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { NForm, NFormItem, NInput } from 'naive-ui';
import { useForm } from '@inertiajs/vue3';

import DeleteModelButton from '../Buttons/DeleteModelButton.vue';
import FormElement from './FormElement.vue';
import UpsertModelButton from '../Buttons/UpsertModelButton.vue';

const props = defineProps<{
  deleteModelRoute: string;
  modelRoute: string;
  navigation: App.Entities.Navigation;
}>()

const form = useForm('navigation', props.navigation);
</script>
