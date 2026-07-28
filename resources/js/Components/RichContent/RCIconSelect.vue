<template>
  <Select :model-value="modelValue || (allowNone ? NONE_VALUE : undefined)"
    @update:model-value="$emit('update:modelValue', $event === NONE_VALUE ? '' : $event as string)">
    <SelectTrigger>
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem v-if="allowNone" :value="NONE_VALUE">
        {{ $t('rich-content.no_icon') }}
      </SelectItem>
      <SelectItem v-for="option in CARD_ICON_OPTIONS" :key="option.value" :value="option.value">
        <div class="flex items-center gap-2">
          <component :is="option.icon" class="h-4 w-4" />
          {{ $t(option.labelKey) }}
        </div>
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { CARD_ICON_OPTIONS } from './cardIcons';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

// Reka's Select can't bind an empty string as a value (it reads as "unset"), so an
// explicit sentinel represents "no icon" and is translated back to '' on the way out.
const NONE_VALUE = '__none__';

defineProps<{
  modelValue?: string;
  /** Prepend a "no icon" option — for optional icons (e.g. hero buttons), unlike
   *  card-stack/carousel where every item always has one. */
  allowNone?: boolean;
}>();

defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();
</script>
