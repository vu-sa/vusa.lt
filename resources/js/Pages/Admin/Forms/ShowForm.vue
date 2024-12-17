<template>
  <ShowPageLayout :model="form" :title="form.name">
    <template #more-options>
      <MoreOptionsButton edit @edit-click="router.visit(route('forms.edit', form.id))" />
    </template>
    <NDataTable :data :columns="registrationColumns" />
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { router, usePage } from "@inertiajs/vue3";

import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";

const props = defineProps<{
  form: App.Entities.Form;
}>();

const data = props.form.registrations.map((registration) => {
  const row = registration
  registration.field_responses.forEach((fieldResponse) => {
    row[fieldResponse.form_field.id] = fieldResponse.response.value;
  });
  return row;
});

const registrationColumns = props.form.form_fields.map((field) => {
  const columns = {
    title: field.label,
    key: field.id,
  };

  if (field.type === 'enum') {
    if (field.use_model_options && field.options_model === "App\\Models\\Tenant") {

      const tenants = usePage().props.tenants;

      columns['render'] = (row) => {
        const tenant = tenants.find((tenant) => tenant.id === row[field.id]);
        return tenant?.shortname;
      }

    } else {
      columns['render'] = (row) => {
        const option = field.options.find((option) => option.value === row[field.id]);
        console.log(option);
        return option?.label[usePage().props.app.locale];
      }
    }
  }

  if (field.type === 'boolean') {
    columns['render'] = (row) => {
      return row[field.id] ? "Taip" : "Ne";
    }
    columns['ellipsis'] = {
      tooltip: true,
    }

  }

  return {
    ...columns,
    width: 150,
  }
});
</script>
