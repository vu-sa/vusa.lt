<template>
  <div class="flex flex-col gap-5">
    <CardOptionsFields v-model="options" />

    <!-- Content -->
    <Field>
      <FieldLabel>{{ $t('rich-content.content') }}</FieldLabel>
      <TiptapEditor v-model="content" preset="full" prose-style />
    </Field>
  </div>
</template>

<script setup lang="ts">
import CardOptionsFields from './CardOptionsFields.vue';

import type { ShadcnCard } from '@/Types/contentParts';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Field, FieldLabel } from '@/Components/ui/field';

const content = defineModel<ShadcnCard['json_content']>();
const options = defineModel<ShadcnCard['options']>('options');
</script>
