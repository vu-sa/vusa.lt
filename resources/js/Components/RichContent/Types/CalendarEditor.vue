<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="modelValue!.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>

    <div class="flex items-center gap-3">
      <Checkbox 
        :model-value="options?.allTenants"
        @update:model-value="options!.allTenants = $event"
      />
      <div class="space-y-0.5">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {{ $t('rich-content.show_all_tenants') }}
        </span>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('rich-content.show_all_tenants_description') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import type { Calendar } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';

const modelValue = defineModel<Calendar['json_content']>();
const options = defineModel<Calendar['options']>('options', { required: true });

// Initialize options on mount if they're null/undefined
onMounted(() => {
  if (!options.value) {
    options.value = { allTenants: false };
  }
});

</script>

