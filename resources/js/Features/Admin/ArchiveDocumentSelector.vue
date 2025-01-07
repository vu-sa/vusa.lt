<template>
  <NForm>
    <NFormItem>
      <template #label>
        Pasirinkite dokumentą
      </template>
      <NSelect v-model:value="selected" filterable :options="options" placeholder="Pasirinkite dokumentą" />
    </NFormItem>
    <NButton type="primary" @click="handleSubmit">
      Pridėti
    </NButton>
  </NForm>
</template>

<script setup lang="ts">
const emit = defineEmits<{
  (e: 'submit', url: string): void
}>();

import { ref } from 'vue';

const selected = ref('');

const options = await fetch(route('api.documents.index'))
  .then((response) => response.json())
  .then((data) => data.map((document) => ({ label: document.title, value: document.anonymous_url })));

function handleSubmit() {
  emit("submit", selected.value);
}
</script>
