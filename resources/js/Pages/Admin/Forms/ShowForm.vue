<template>
  <ShowPageLayout :model="form" :title="form.name">
    <template #more-options>
      <MoreOptionsButton edit @edit-click="router.visit(route('forms.edit', form.id))" />
    </template>
    <NDataTable :data :columns="registrationColumns" />
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { router } from "@inertiajs/vue3";

import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";

const props = defineProps<{
  form: App.Entities.Form;
}>();

const data = props.form.registrations.map((registration) => {
  const row = registration
  registration.field_responses.forEach((fieldResponse) => {
    row[fieldResponse.form_field.id] = fieldResponse.response;
  });
  return row;
});

const registrationColumns = props.form.form_fields.map((field) => ({
  title: field.label,
  key: field.id,
  render(row) {
    if (field.type === "boolean") {
      return row[field.id] ? "Taip" : "Ne";
    }
    return row[field.id];
  },
}));

console.log(props.form.registrations, registrationColumns);

</script>
