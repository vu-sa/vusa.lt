<template>
  <div>
    <div data-slot="card-surface" class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card transition-all duration-300 hover:border-brand">
      <div v-if="element.options?.title || editable" data-slot="card-header" class="px-5 pt-5 pb-3">
        <RCInlineText
          as="h3" data-slot="card-title" class="mb-1.5 text-2xl font-bold leading-tight tracking-tight text-foreground"
          :model-value="element.options?.title ?? ''" :editable :placeholder="$t('rich-content.title')"
          @update:model-value="$emit('update:element', { ...element, options: { ...element.options, title: $event } })"
        />
      </div>
      <div class="relative rc-prose tracking-normal px-5 pb-5" :class="{ 'pt-5': !element.options?.title && !editable }">
        <template v-if="editable">
          <TiptapEditor
            v-if="isBodyActive"
            :model-value="element.json_content" preset="full" prose-style
            :placeholder="$t('rich-content.content')"
            @update:model-value="$emit('update:element', { ...element, json_content: $event })"
          />
          <div v-else
            class="min-h-12 cursor-text" data-rc-interactive data-rc-card-content
            role="button" tabindex="0"
            @click="$emit('claim-inline-field', bodyFieldId)"
            @keydown.enter.prevent="$emit('claim-inline-field', bodyFieldId)"
            @keydown.space.prevent="$emit('claim-inline-field', bodyFieldId)"
          >
            <RichContentTiptapHTML v-if="hasBody" :json_content="element.json_content" />
            <p v-else class="italic text-muted-foreground/60">{{ $t('rich-content.content') }}</p>
          </div>
          <RCAddPlaceholder
            v-if="!hasBody && !isBodyActive"
            :label="$t('rich-content.content')"
            class="left-1/2 -bottom-2 -translate-x-1/2 translate-y-full"
            @click="$emit('claim-inline-field', bodyFieldId)"
          />
        </template>
        <slot v-else />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCInlineText from './Editor/Fullscreen/RCInlineText.vue';
import RCAddPlaceholder from './Editor/Fullscreen/RCAddPlaceholder.vue';
import RichContentTiptapHTML from './RichContentTiptapHTML.vue';

import type { ShadcnCard } from '@/Types/contentParts';

const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

const props = defineProps<{
  element: ShadcnCard;
  editable?: boolean;
  blockKey?: string;
  activeInlineField?: string | null;
}>();

defineEmits<{
  (e: 'update:element', value: ShadcnCard): void;
  (e: 'claim-inline-field', field: string | null): void;
}>();

const bodyFieldId = computed(() => `${props.blockKey ?? ''}:body`);
const isBodyActive = computed(() => props.activeInlineField === bodyFieldId.value);
const hasBody = computed(() => hasContent(props.element.json_content));

function hasContent(node: unknown): boolean {
  if (!node || typeof node !== 'object') return false;
  const { type, text, content } = node as { type?: string; text?: string; content?: unknown[] };
  if (typeof text === 'string' && text.trim()) return true;
  if (type && type !== 'doc' && type !== 'paragraph') return true;
  return (content ?? []).some(hasContent);
}
</script>
