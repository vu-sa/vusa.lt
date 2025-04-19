<template>
  <Select
    v-model="modelValue"
    :disabled="disabled"
  >
    <SelectTrigger class="w-[180px]">
      <SelectValue :placeholder="$t('tasks.filters.select_filter')" />
    </SelectTrigger>
    <SelectContent>
      <SelectGroup>
        <SelectLabel>{{ $t("tasks.filters.label") }}</SelectLabel>
        <SelectItem v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </SelectItem>
      </SelectGroup>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

interface FilterOption {
  label: string;
  value: string;
}

const props = defineProps<{
  disabled?: boolean;
  options: FilterOption[];
}>();

const modelValue = defineModel<string>('modelValue');
</script>
