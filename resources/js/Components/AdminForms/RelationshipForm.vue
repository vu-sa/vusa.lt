<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <Input id="name" v-model="form.name" type="text" placeholder="Trumpas ryšio pavadinimas.." />
      </FormFieldWrapper>
      <FormFieldWrapper id="slug" label="Techninė žymė">
        <Input id="slug" v-model="form.slug" type="text" placeholder="pvz.: simple-advisory" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" label="Aprašymas">
        <Textarea id="description" v-model="form.description" placeholder="Trumpas apibūdinimas..." />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import AdminForm from "./AdminForm.vue";

const { relationship, rememberKey } = defineProps<{
  relationship: App.Entities.Relationship;
  rememberKey?: "CreateRelationship";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = rememberKey ? useForm(rememberKey, relationship) : useForm(relationship);
</script>
