<template>
  <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card transition-all duration-300 hover:border-brand">
    <div v-if="element.options?.title || editable" data-slot="card-header" class="px-5 pt-5 pb-3">
      <RCInlineText
        as="h3" data-slot="card-title" class="text-lg font-bold leading-tight tracking-tight mb-1.5 text-foreground"
        :model-value="element.options?.title ?? ''" :editable :placeholder="$t('rich-content.title')"
        @update:model-value="$emit('update:element', { ...element, options: { ...element.options, title: $event } })"
      />
    </div>
    <div class="rc-prose tracking-normal px-5 pb-5" :class="{ 'pt-5': !element.options?.title && !editable }">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import RCInlineText from './Editor/Fullscreen/RCInlineText.vue';

defineProps<{
  element: models.ContentPart;
  editable?: boolean;
}>();

defineEmits<{
  (e: 'update:element', value: models.ContentPart): void;
}>();
</script>
