<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <MdSuspenseWrapper directory="tags" :locale="$page.props.app.locale" file="description" />
      </template>

      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <MultiLocaleTiptapFormItem v-model:input="form.description" label="Aprašymas" />

      <FormFieldWrapper id="alias" label="Alias" helper-text="Papildomas pavadinimas žymai (neprivalomas). Jei neįvestas, bus sugeneruotas automatiškai iš pavadinimo.">
        <Input id="alias" v-model="form.alias" placeholder="Pvz: stipendijos" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import { Input } from "@/Components/ui/input";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import AdminForm from "./AdminForm.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import MultiLocaleTiptapFormItem from "@/Components/FormItems/MultiLocaleTiptapFormItem.vue";
import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";

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
