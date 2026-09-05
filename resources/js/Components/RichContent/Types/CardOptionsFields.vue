<template>
  <Field>
    <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
    <Input :model-value="options.title" type="text" :placeholder="$t('rich-content.enter_title')" @update:model-value="patchOptions({ title: $event })" />
  </Field>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { ShadcnCard } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const options = defineModel<ShadcnCard['options']>({ required: true });

function patchOptions(patch: Partial<ShadcnCard['options']>): void {
  options.value = { ...options.value, ...patch };
}
</script>
