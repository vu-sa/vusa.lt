<template>
  <div class="mt-4 flex flex-col gap-4">
    <NFormItem label="Pavadinimas" :show-feedback="false">
      <NInput v-model:value="modelValue.title" type="text" />
    </NFormItem>
    <NFormItem label="Rodyti visų padalinių renginius" :show-feedback="false">
      <NCheckbox :checked="allTenantsBoolean" @update:checked="setAllTenants">
        Įjungti, jei norite rodyti visų VU SA padalinių renginius
      </NCheckbox>
    </NFormItem>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { NCheckbox } from 'naive-ui';
import type { Calendar } from '@/Types/contentParts';

const modelValue = defineModel<Calendar['json_content']>();
const options = defineModel<Calendar['options']>('options', { required: true });

// Initialize options on mount if they're null/undefined
onMounted(() => {
  if (!options.value) {
    options.value = { allTenants: false };
  }
});

// Ensure we always work with proper booleans
const allTenantsBoolean = computed(() => {
  const val = options.value?.allTenants;
  // Handle string "1", number 1, true, or truthy values
  return val === true || val === 1 || val === '1' || val === 'true';
});

const setAllTenants = (checked: boolean) => {
  if (!options.value) {
    options.value = { allTenants: false };
  }
  // Explicitly set as boolean
  options.value.allTenants = Boolean(checked);
};
</script>

