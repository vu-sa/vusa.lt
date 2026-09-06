<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.eyebrow') }}</FieldLabel>
      <Input v-model="modelValue!.eyebrow" type="text" :placeholder="$t('rich-content.enter_eyebrow')" />
    </Field>

    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="modelValue!.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>

    <InstitutionListOptionsFields v-model="options" />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

import InstitutionListOptionsFields from './InstitutionListOptionsFields.vue';

import type { InstitutionList } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const modelValue = defineModel<InstitutionList['json_content']>({ required: true });
const options = defineModel<InstitutionList['options']>('options', { required: true });

onMounted(() => {
  if (!options.value) {
    options.value = { tenantScope: 'all', typeSlug: 'pkp', limit: null };
  }
});
</script>
