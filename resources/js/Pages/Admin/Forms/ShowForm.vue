<template>
  <ShowPageLayout :model="form" :title="form.name">
    <template #more-options>
      <MoreOptionsButton edit @edit-click="router.visit(route('forms.edit', form.id))" />
    </template>
    <NDataTable scroll-x="800" :data :columns="registrationColumns" />
    <CardModal v-model:show="showModal" title="Registracijos informacija" @close="showModal = false">
      <!-- show data for the selected registration not in a row, but in a grid -->
      <div v-if="selectedRegistration" class="grid grid-cols-3 gap-8">
        <div v-for="column in registrationColumns" :key="column.key" class="last:hidden">
          <div class="font-semibold">
            {{ column.title }}
          </div>
          <div>{{ selectedRegistration[column.key] }}</div>
        </div>
      </div>
    </CardModal>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { router, usePage } from "@inertiajs/vue3";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";

import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import Eye16Regular from "~icons/fluent/eye16-regular";

const props = defineProps<{
  form: App.Entities.Form;
}>();

const showModal = ref(false);
const selectedRegistration = ref(null);

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

      //} else {
      //  columns['render'] = (row) => {
      //    const option = field.options.find((option) => option.value === row[field.id]);
      //    return option?.label[usePage().props.app.locale];
      //  }
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

registrationColumns.unshift({
  title: "ID",
  key: "id",
  width: 50,
});

// add created_at column
registrationColumns.push({
  title: "Sukurta",
  key: "created_at",
  width: 150,
  render(row) {
    return new Date(row.created_at).toLocaleString();
  },
});

registrationColumns.push({
  key: "actions",
  width: 60,
  fixed: "right",
  render(row) {
    return (
      <div class="flex justify-center">
        <NButton
          size="tiny"
          type="primary"
          onClick={() => {
            selectedRegistration.value = row;
            showModal.value = true;
          }}
        >
          {{ icon: () => <NIcon component={Eye16Regular} /> }}
        </NButton>
      </div>
    );
  },
});
</script>
