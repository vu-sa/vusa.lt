<template>
  <div class="relative">
    <div v-if="typeof element.html === 'string'" class="rc-prose" v-html="element.html" />
    <div v-else-if="element.html === null" class="text-sm italic text-muted-foreground">
      {{ $t('Turinio nepavyko atvaizduoti') }}
    </div>
    <RichContentTiptapHTML v-else-if="element.json_content" :json_content="element.json_content" />
  </div>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

const RichContentTiptapHTML = defineAsyncComponent(() => import('../RichContentTiptapHTML.vue'));
defineProps<{
  html?: boolean;
  element: {
    id?: number;
    type?: string;
    html?: string | null;
    json_content?: Record<string, unknown> | null;
    options?: Record<string, unknown> | null;
  };
}>();
</script>
