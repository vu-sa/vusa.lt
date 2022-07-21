<template>
  <div class="main-card">
    <NAlert
      v-if="getErrors()"
      class="mb-4"
      title="Pataisykite klaidas"
      type="error"
    >
      <ul class="list-inside">
        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
      </ul>
    </NAlert>
    <slot />
    <div class="flex justify-end">
      <UpsertModelButton :model="formValue" model-route="calendar.store"
        >Sukurti</UpsertModelButton
      >
    </div>
  </div>
</template>

<script setup lang="ts">
import { NAlert } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

defineProps<{
  formValue: App.Models.ModelTemplate;
}>();

const inertiaProps = ref(usePage<InertiaProps>().props.value);
const errors = ref(inertiaProps.value.errors);

const getErrors = () => {
  // check if object is empty
  return Object.keys(errors.value).length > 0;
};
</script>
