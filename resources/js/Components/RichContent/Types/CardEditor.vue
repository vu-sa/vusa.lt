<template>
  <div class="flex flex-col gap-5">
    <!-- Title -->
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="options.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>

    <!-- Content -->
    <Field>
      <FieldLabel>{{ $t('rich-content.content') }}</FieldLabel>
      <TiptapEditor v-model="content" preset="full" prose-style />
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { ShadcnCard } from '@/Types/contentParts';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const content = defineModel<ShadcnCard['json_content']>();
const options = defineModel<ShadcnCard['options']>('options');
</script>
