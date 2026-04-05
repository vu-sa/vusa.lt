<template>
  <div class="flex flex-col gap-4">
    <Alert>
      <IFluentInfoIcon class="size-4" />
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

    <div class="flex items-start gap-3">
      <Checkbox
        :model-value="options?.isClosed"
        @update:model-value="options!.isClosed = $event"
      />
      <div class="space-y-0.5">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {{ $t('rich-content.text_box_closed_label') }}
        </span>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('rich-content.text_box_closed_description') }}
        </p>
      </div>
    </div>

    <Field v-if="options?.isClosed">
      <FieldLabel>{{ $t('rich-content.text_box_closed_message_label') }}</FieldLabel>
      <MultiLocaleInput v-model:input="options!.closedMessage" input-type="textarea" />
    </Field>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

import type { TextBox } from '@/Types/contentParts';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Checkbox } from '@/Components/ui/checkbox';
import { Field, FieldLabel } from '@/Components/ui/field';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import IFluentInfoIcon from '~icons/fluent/info-24-regular';

const modelValue = defineModel<TextBox['json_content']>();
const options = defineModel<TextBox['options']>('options', { required: true });

onMounted(() => {
  if (options.value && !options.value.closedMessage) {
    options.value.closedMessage = { lt: '', en: '' };
  }
});
</script>
