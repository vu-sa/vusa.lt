<template>
  <Select :model-value="modelValue || NONE" @update:model-value="onChange">
    <SelectTrigger>
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem :value="NONE">
        -- {{ $t('Visi tipai') }} --
      </SelectItem>
      <SelectItem v-for="type in types" :key="type.id" :value="type.slug">
        {{ type.title }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const NONE = '__none__';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const types = computed(() => (page.props.institutionTypes as Array<{ id: number; title: string; slug: string }> ?? []).filter(t => !!t.slug));

function onChange(value: unknown): void {
  modelValue.value = value === NONE ? undefined : String(value);
}
</script>
