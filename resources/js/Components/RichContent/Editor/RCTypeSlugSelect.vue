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

import type { PageProps } from '@/Types/index';
import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';

const modelValue = defineModel<string | undefined>();

const page = usePage<PageProps>();
const types = computed(() => page.props.institutionTypes ?? []);

// The block stores the slug; the picker works on ids.
const typeId = computed({
  get: () => types.value.find(type => type.slug && type.slug === modelValue.value)?.id ?? null,
  set: (id) => {
    modelValue.value = types.value.find(type => type.id === id)?.slug ?? undefined;
  },
});
</script>
