<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p class="mb-4">
          Žyma – tai etiketė, kuri padeda kategorizuoti ir organizuoti turinį (naujienas, puslapius).
        </p>
      </template>
      
      <NFormItem :label="$t('forms.fields.title')" :required="true">
        <MultiLocaleInput v-model:input="form.name" />
      </NFormItem>

      <NFormItem :label="$t('forms.fields.description')">
        <MultiLocaleTiptapFormItem v-model:input="form.description" label="Aprašymas" />
      </NFormItem>

      <NFormItem label="Alias" help="Papildomas pavadinimas žymai (neprivalomas). Jei neįvestas, bus sugeneruotas automatiškai iš pavadinimo.">
        <NInput v-model:value="form.alias" placeholder="Pvz: stipendijos" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import MultiLocaleTiptapFormItem from "@/Components/FormItems/MultiLocaleTiptapFormItem.vue";

const { postTag, rememberKey } = defineProps<{
  postTag: App.Entities.Tag;
  rememberKey?: "CreateTag";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = rememberKey ? useForm(rememberKey, postTag as any) : useForm(postTag as any);
</script>
