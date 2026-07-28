<template>
  <div class="flex flex-col gap-5">
    <!-- Card Stack Options -->
    <Field>
      <FieldLabel>{{ $t('rich-content.card_stack_options') }}</FieldLabel>
      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <Switch v-model="options.autoplay" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.enable_autoplay') }}
          </span>
        </div>
        <div v-if="options.autoplay" class="flex items-center gap-3">
          <FieldLabel class="min-w-fit">{{ $t('rich-content.autoplay_delay') }}</FieldLabel>
          <Input
            v-model.number="options.autoplayDelay"
            type="number"
            min="2000"
            max="30000"
            step="1000"
            class="w-24"
          />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $t('rich-content.milliseconds') }}</span>
        </div>
        <Field>
          <FieldLabel>{{ $t('rich-content.hint_text') }}</FieldLabel>
          <Input
            v-model="options.hintText"
            type="text"
            :placeholder="$t('rich-content.enter_hint_text')"
          />
        </Field>
      </div>
    </Field>

    <RCSectionOptions v-model="options" />

    <!-- Cards -->
    <Field>
      <FieldLabel>{{ $t('rich-content.cards') }}</FieldLabel>
      <DynamicListInput
        v-model="json_content"
        :create-item="createCard"
        :empty-text="$t('rich-content.no_cards')"
        :add-first-text="$t('rich-content.add_first_card')"
        :add-text="$t('rich-content.add_card')"
        compact>
        <template #item="{ item, update }">
          <div class="flex flex-col gap-3">
            <Field>
              <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
              <RCIconSelect allow-none :model-value="item.icon" @update:model-value="update({ ...item, icon: $event })" />
            </Field>
            <Field>
              <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
              <Input
                :model-value="item.title"
                type="text"
                :placeholder="$t('rich-content.enter_title')"
                @update:model-value="update({ ...item, title: $event })"
              />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.description') }}</FieldLabel>
              <Input
                :model-value="item.description"
                type="text"
                :placeholder="$t('rich-content.enter_description')"
                @update:model-value="update({ ...item, description: $event })"
              />
            </Field>
          </div>
        </template>
      </DynamicListInput>
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { CardStack } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import RCIconSelect from '../RCIconSelect.vue';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';

const options = defineModel<CardStack['options']>('options', { default: () => ({ autoplay: true, autoplayDelay: 5000, hintText: '' }) });
const json_content = defineModel<CardStack['json_content']>({ default: () => [] });

function createCard(): CardStack['json_content'][number] {
  return {
    icon: '',
    title: '',
    description: '',
  };
}
</script>