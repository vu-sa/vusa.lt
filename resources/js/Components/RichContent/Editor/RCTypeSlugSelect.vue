<template>
  <TypeTreeSelect
    v-model="typeId"
    :types
    :omit="types.filter(type => !type.slug).map(type => type.id)"
    :placeholder="`-- ${$t('Visi tipai')} --`"
    placeholder-selectable
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const types = computed(() => (page.props.institutionTypes as Array<{ id: number; title: string; slug: string | null; parent_id?: number | null }> | undefined) ?? []);

// The block stores the slug; the picker works on ids.
const typeId = computed({
  get: () => types.value.find(type => type.slug && type.slug === modelValue.value)?.id ?? null,
  set: (id) => {
    modelValue.value = types.value.find(type => type.id === id)?.slug ?? undefined;
  },
});
</script>
