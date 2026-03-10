<template>
  <div class="flex flex-col gap-4">
    <Alert>
      <IFluentShieldLockIcon class="size-4" />
      <AlertTitle>{{ $t('rich-content.text_box_privacy_notice') }}</AlertTitle>
      <AlertDescription>{{ $t('rich-content.text_box_privacy_notice_text') }}</AlertDescription>
    </Alert>
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <MultiLocaleInput v-model:input="options!.title" />
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.text_box_placeholder_label') }}</FieldLabel>
      <MultiLocaleInput v-model:input="options!.placeholder" input-type="textarea" />
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { TextBox } from '@/Types/contentParts';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Field, FieldLabel } from '@/Components/ui/field';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import IFluentShieldLockIcon from '~icons/fluent/info-24-regular';

const modelValue = defineModel<TextBox['json_content']>();
const options = defineModel<TextBox['options']>('options', { required: true });
</script>
