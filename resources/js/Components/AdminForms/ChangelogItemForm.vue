<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        Pasikeitimai rodomi visiems lankytojams nuo nurodytos datos.
      </template>
      <NFormItem label="Pavadinimas" :required="true">
        <MultiLocaleInput v-model:input="form.title" />
      </NFormItem>
      <NFormItem label="Aprašymas">
        <OriginalTipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <OriginalTipTap v-if="locale === 'en'" v-model="form.description.en" html />
      </NFormItem>
      <NFormItem label="Data">
        <NDatePicker v-model:formatted-value="form.date" type="datetime" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { NDatePicker, NFormItem } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import OriginalTipTap from "../TipTap/OriginalTipTap.vue";
import AdminForm from "./AdminForm.vue";

const { changelogItem, rememberKey } = defineProps<{
  changelogItem: App.Entities.ChangelogItem;
  rememberKey?: string;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const locale = ref<"lt" | "en">("lt");

const form = rememberKey
  ? useForm(rememberKey, changelogItem)
  : useForm(changelogItem);
</script>
