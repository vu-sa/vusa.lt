<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
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
import { NForm, NFormItem, NInput } from "naive-ui";
import { useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  category: App.Entities.Category;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("category", props.category);
</script>
