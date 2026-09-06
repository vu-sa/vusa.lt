<template>
  <Select :model-value="modelValue || NONE" @update:model-value="onChange">
    <SelectTrigger>
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem :value="NONE">
        -- {{ $t('rich-content.tag_alias_none') }} --
      </SelectItem>
      <SelectItem v-for="tag in tags" :key="tag.id" :value="tag.alias">
        {{ tag.name }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const NONE = '__none__';
const modelValue = defineModel<string | undefined>();

const page = usePage();
const tags = computed(() => (page.props.tags ?? []).filter(tag => !!tag.alias));

function onChange(value: unknown): void {
  modelValue.value = value === NONE ? undefined : String(value);
}
</script>
